<?php

namespace App\Services\Seo;

use App\Models\Domain;
use App\Support\Typed;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 域名监控：whois 到期日 / registrar / NS
 *
 * 双通道（M30）：whois 43 端口是明文 TCP，部分云主机安全组/机房默认禁出站 43，
 * 导致「注册商/到期时间获取不到」。whois 查询失败或解析不到期日时，
 * 自动回退 RDAP（HTTP 443，rdap.org 引导至注册局 RDAP）——出门只需 443 放行。
 */
class DomainMonitor
{
    /**
     * @return array{ok:bool, expiration_date?:string, registrar?:string|null, nameservers?:array<int, string>, error?:string}
     */
    public function whois(string $domain): array
    {
        $result = $this->socketWhois($domain);

        // M30：43 端口被禁 / 解析失败 → RDAP（HTTP 443）回退，字段级择优合并
        $rdap = $this->rdap($domain);

        if ($rdap !== null) {
            if (($result['expiration_date'] ?? null) === null && isset($rdap['expiration_date'])) {
                $result['expiration_date'] = $rdap['expiration_date'];
                unset($result['error']);
            }
            if (($result['registrar'] ?? null) === null && isset($rdap['registrar'])) {
                $result['registrar'] = $rdap['registrar'];
            }
            if (($result['nameservers'] ?? null) === null && isset($rdap['nameservers'])) {
                $result['nameservers'] = $rdap['nameservers'];
            }

            if (($result['ok'] ?? false) === false && isset($result['expiration_date'])) {
                $result['ok'] = true;
                unset($result['error']);
            }
        }

        if (($result['expiration_date'] ?? null) === null) {
            return [
                'ok' => false,
                'error' => $result['error'] ?? '未解析出到期日期',
                'registrar' => $result['registrar'] ?? null,
                'nameservers' => $result['nameservers'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'expiration_date' => $result['expiration_date'],
            'registrar' => $result['registrar'] ?? null,
            'nameservers' => $result['nameservers'] ?? null,
        ];
    }

    /**
     * 传统 socket whois（43 端口）
     *
     * @return array{ok:bool, expiration_date?:string, registrar?:string|null, nameservers?:array<int, string>, error?:string}
     */
    protected function socketWhois(string $domain): array
    {
        $server = static::whoisServer($domain);

        if ($server === null) {
            return ['ok' => false, 'error' => '不支持的顶级域名'];
        }

        $raw = $this->query($server, $domain);

        if ($raw === null) {
            // whois.iana.org 引导跳转（referral-hop 断言 · 安全审计周期 #19）：
            // - referral 目标必须是合法 whois 主机（纯域名格式，杜绝端口/路径注入）
            // - 不得指回 whois.iana.org 自身（无意义循环）
            // - 仅允许一跳（跳转后仍返回 referral 时不再继续追，防无限链）
            $raw = $this->query('whois.iana.org', $domain);

            if ($raw !== null
                && preg_match('/whois:\s*(\S+)/i', $raw, $m) > 0
                && strcasecmp($m[1], 'whois.iana.org') !== 0
                && preg_match('/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+\.?$/i', rtrim($m[1], '.')) > 0) {
                $raw = $this->query($m[1], $domain) ?? $raw;
            }
        }

        if ($raw === null) {
            return ['ok' => false, 'error' => 'whois 服务器连接失败'];
        }

        $expiration = static::matchDate($raw, ['Registry Expiry Date', 'Expiration Date', 'Expiry Date', 'paid-till', 'Expiration Time']);
        $registrar = static::matchField($raw, 'Registrar');
        $nameservers = static::matchNameservers($raw);

        if ($expiration === null) {
            return ['ok' => false, 'error' => '未解析出到期日期', 'registrar' => $registrar, 'nameservers' => $nameservers];
        }

        return [
            'ok' => true,
            'expiration_date' => $expiration,
            'registrar' => $registrar,
            'nameservers' => $nameservers,
        ];
    }

    /**
     * RDAP 回退（M30）：HTTP 443 查询，rdap.org 302 引导至注册局 RDAP 端点
     *
     * @return array{expiration_date?:string, registrar?:string, nameservers?:array<int, string>}|null
     */
    public function rdap(string $domain): ?array
    {
        try {
            $response = Http::timeout(15)->connectTimeout(8)->get('https://rdap.org/domain/'.rawurlencode(strtolower($domain)));

            if ($response->failed()) {
                return null;
            }

            $json = $response->json();
            if (! is_array($json)) {
                return null;
            }

            $result = [];

            // events[].eventAction=expiration → eventDate（ISO8601 取日期部分）
            foreach ((array) ($json['events'] ?? []) as $event) {
                if ((string) ($event['eventAction'] ?? '') === 'expiration') {
                    $date = Typed::stringOrNull($event['eventDate'] ?? null);
                    if ($date !== null && preg_match('/(\d{4}-\d{2}-\d{2})/', $date, $m) > 0) {
                        $result['expiration_date'] = $m[1];
                    }

                    break;
                }
            }

            // entities[] roles 含 registrar → vcard fn / publicIds / handle
            foreach ((array) ($json['entities'] ?? []) as $entity) {
                if (in_array('registrar', (array) ($entity['roles'] ?? []), true)) {
                    $registrar = static::vcardName($entity['vcard'] ?? null)
                        ?? (is_array($entity['publicIds'] ?? null) && isset($entity['publicIds'][0]['identifier'])
                            ? (string) $entity['publicIds'][0]['identifier']
                            : null)
                        ?? (isset($entity['handle']) ? (string) $entity['handle'] : null);

                    if ($registrar !== null && $registrar !== '') {
                        $result['registrar'] = mb_substr($registrar, 0, 128);
                    }

                    break;
                }
            }

            // nameservers[].ldhName
            $ns = [];
            foreach ((array) ($json['nameservers'] ?? []) as $nameserver) {
                $ldh = strtolower((string) ($nameserver['ldhName'] ?? ''));
                if ($ldh !== '') {
                    $ns[] = rtrim($ldh, '.');
                }
            }
            if ($ns !== []) {
                $result['nameservers'] = array_values(array_unique($ns));
            }

            return ($result === []) ? null : $result;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * RDAP vcard 数组取 FN（显示名）
     */
    protected static function vcardName(mixed $vcard): ?string
    {
        if (! is_array($vcard) || ! is_array($vcard[1] ?? null)) {
            return null;
        }

        foreach ($vcard[1] as $entry) {
            if (is_array($entry) && ($entry[0] ?? null) === 'fn' && isset($entry[3])) {
                return trim((string) $entry[3]);
            }
        }

        return null;
    }

    /**
     * 复检单个域名并写回监控列；返回距到期天数（null = 检查失败）
     */
    public function refresh(Domain $domain): ?int
    {
        $result = $this->whois($domain->host);

        $domain->update([
            'monitor_last_check_at' => now(),
            'monitor_expiration_date' => $result['expiration_date'] ?? $domain->monitor_expiration_date,
            'monitor_registrar' => $result['registrar'] ?? null,
            'monitor_nameservers' => isset($result['nameservers']) ? implode(', ', $result['nameservers']) : null,
        ]);

        if (! $result['ok'] || $domain->monitor_expiration_date === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($domain->monitor_expiration_date, false);
    }

    protected function query(string $server, string $domain): ?string
    {
        try {
            $socket = @fsockopen($server, 43, $errorCode, $errorString, 10);

            if ($socket === false) {
                return null;
            }

            fwrite($socket, $domain."\r\n");

            $response = '';
            while (! feof($socket)) {
                $response .= fread($socket, 4096);
            }
            fclose($socket);

            return $response;
        } catch (Throwable) {
            return null;
        }
    }

    protected static function whoisServer(string $domain): ?string
    {
        $dot = strrchr($domain, '.');
        $tld = strtolower($dot === false ? '' : substr($dot, 1));

        return match ($tld) {
            'com', 'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'info' => 'whois.afilias.net',
            'io' => 'whois.nic.io',
            'cn' => 'whois.cnnic.cn',
            'jp' => 'whois.jprs.jp',
            'ru' => 'whois.tcinet.ru',
            'uk' => 'whois.nic.uk',
            'de' => 'whois.denic.de',
            'me' => 'whois.nic.me',
            'tv' => 'whois.nic.tv',
            'cc' => 'whois.nic.cc',
            'xyz' => 'whois.nic.xyz',
            'top' => 'whois.nic.top',
            'vip' => 'whois.nic.vip',
            default => 'whois.iana.org',
        };
    }

    /**
     * @param  array<int, string>  $fields
     */
    protected static function matchDate(string $raw, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (preg_match('/'.preg_quote($field, '/').':\s*(.+)/i', $raw, $m) > 0) {
                $value = trim($m[1]);

                // ISO 格式（2026-08-31T08:00:00Z）取日期部分
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $d) > 0) {
                    return $d[1];
                }

                $timestamp = strtotime($value);

                if ($timestamp !== false) {
                    return date('Y-m-d', $timestamp);
                }
            }
        }

        return null;
    }

    protected static function matchField(string $raw, string $field): ?string
    {
        return preg_match('/'.preg_quote($field, '/').':\s*(.+)/i', $raw, $m) > 0 ? trim($m[1]) : null;
    }

    /**
     * @return array<int, string>
     */
    protected static function matchNameservers(string $raw): array
    {
        preg_match_all('/Name Server:\s*(\S+)/i', $raw, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1])));
    }
}
