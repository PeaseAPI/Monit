<?php

namespace App\Services\Seo;

use App\Models\Domain;
use Throwable;

/**
 * 域名监控：whois 到期日 / registrar / NS（纯 socket 实现，无扩展依赖）
 */
class DomainMonitor
{
    /**
     * @return array{ok:bool, expiration_date?:string, registrar?:string, nameservers?:array, error?:string}
     */
    public function whois(string $domain): array
    {
        $server = static::whoisServer($domain);

        if ($server === null) {
            return ['ok' => false, 'error' => '不支持的顶级域名'];
        }

        $raw = $this->query($server, $domain);

        if ($raw === null) {
            // 尝试 whois.iana.org 引导跳转
            $raw = $this->query('whois.iana.org', $domain);

            if ($raw !== null && preg_match('/whois:\s*(\S+)/i', $raw, $m)) {
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
        $tld = strtolower((string) substr(strrchr($domain, '.'), 1));

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

    protected static function matchDate(string $raw, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (preg_match('/'.preg_quote($field, '/').':\s*(.+)/i', $raw, $m)) {
                $value = trim($m[1]);

                // ISO 格式（2026-08-31T08:00:00Z）取日期部分
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $value, $d)) {
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
        return preg_match('/'.preg_quote($field, '/').':\s*(.+)/i', $raw, $m) ? trim($m[1]) : null;
    }

    protected static function matchNameservers(string $raw): array
    {
        preg_match_all('/Name Server:\s*(\S+)/i', $raw, $matches);

        return array_values(array_unique(array_map('strtolower', $matches[1] ?? [])));
    }
}
