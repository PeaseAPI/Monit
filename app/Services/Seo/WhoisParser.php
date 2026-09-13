<?php

namespace App\Services\Seo;

use App\Support\Typed;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 全字段 WHOIS 解析（对标 chinaz whois 页：注册/更新/过期时间、域名年龄、
 * 注册商联络、注册人、域名状态中文释义、DNSSEC、原始记录）
 *
 * 数据双源合并：
 *  - 传统 socket whois 原始文本（DomainMonitor::rawQuery）→ parseRaw() 正则解析
 *  - RDAP JSON（rdap.org 302 引导至注册局）→ 结构化 events/status/entities
 * 字段级择优：raw 缺失的用 RDAP 补；日期统一 Y-m-d。
 */
class WhoisParser
{
    /** 域名 EPP 状态码 → 中文释义（与 chinaz WHOIS FAQ 一致） */
    public const STATUS_ZH = [
        'clientLock' => '注册商锁定',
        'serverLock' => '注册局锁定',
        'renewPeriod' => '注册商续费',
        'pendingUpdate' => '即将更新',
        'pendingRestore' => '即将恢复',
        'pendingRenew' => '即将续费',
        'pendingCreate' => '即将创建',
        'autoRenewPeriod' => '注册局自动续费',
        'transferPeriod' => '注册商转移期',
        'addPeriod' => '域名新注册期',
        'ok' => '正常',
        'inactive' => '非激活状态',
        'clientDeleteProhibited' => '注册商设置禁止删除',
        'serverDeleteProhibited' => '注册局设置禁止删除',
        'clientUpdateProhibited' => '注册商设置禁止更新',
        'serverUpdateProhibited' => '注册局设置禁止更新',
        'clientHold' => '注册商设置暂停解析',
        'serverHold' => '注册局设置暂停解析',
        'pendingVerification' => '注册信息审核期',
        'redemptionPeriod' => '注册局设置赎回期',
        'clientTransferProhibited' => '注册商设置禁止转移',
        'serverTransferProhibited' => '注册局设置禁止转移',
        'clientRenewProhibited' => '注册商设置禁止续费',
        'serverRenewProhibited' => '注册局设置禁止续费',
        'pendingDelete' => '注册局设置待删除',
        'pendingTransfer' => '注册局设置转移过程中',
    ];

    public function __construct(protected DomainMonitor $monitor) {}

    /**
     * 全字段 WHOIS 查询
     *
     * @return array{ok: bool, error?: string, data: array<string, string>, raw: ?string}
     */
    public function detailed(string $domain): array
    {
        $domain = strtolower(rtrim(trim($domain), '.'));

        if ($domain === '' || ! preg_match('/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $domain)) {
            return ['ok' => false, 'error' => '域名格式无效', 'data' => [], 'raw' => null];
        }

        $raw = $this->monitor->rawQuery($domain);
        $parsed = $raw !== null ? $this->parseRaw($raw) : [];
        $rdap = $this->fetchRdap($domain);

        if ($raw === null && $rdap === []) {
            return ['ok' => false, 'error' => 'WHOIS 查询失败（43 端口与 RDAP 均不可达）', 'data' => [], 'raw' => null];
        }

        $get = fn (string $key): ?string => Typed::stringOrNull($parsed[$key] ?? $rdap[$key] ?? null);

        $creation = $this->normalizeDate($get('creation'));
        $updated = $this->normalizeDate($get('updated'));
        $expiry = $this->normalizeDate($get('expiry'));
        $statuses = $parsed['statuses'] ?? $rdap['statuses'] ?? [];

        $data = [
            '域名' => $domain,
            '注册时间' => $creation ?? '-',
            '更新时间' => $updated ?? '-',
            '过期时间' => $expiry ?? '-',
        ];

        if ($creation !== null) {
            $data['域名年龄'] = $this->humanAge($creation);
        }

        if ($expiry !== null) {
            $data['到期剩余'] = $this->remainingDays($expiry);
        }

        $data += [
            '注册商' => $get('registrar') ?? '-',
            '注册商网址' => $get('registrar_url') ?? '-',
            '注册商 WHOIS 服务器' => $get('registrar_whois') ?? '-',
            '注册商邮箱' => $get('registrar_email') ?? '-',
            '注册商电话' => $get('registrar_phone') ?? '-',
            '注册人/机构' => $get('registrant') ?? '-',
            '注册人邮箱' => $get('registrant_email') ?? '-',
            '域名状态' => $statuses !== [] ? implode('；', $statuses) : '-',
            'DNSSEC' => $get('dnssec') ?? '-',
            '域名服务器' => $get('nameservers') ?? '-',
        ];

        // 全「-」的字段集不展示（整体失败已提前返回，此处兜底过滤无效结果）
        if (($data['过期时间'] ?? '-') === '-' && ($data['注册商'] ?? '-') === '-' && ($data['注册时间'] ?? '-') === '-') {
            return ['ok' => false, 'error' => '未解析到有效 WHOIS 字段', 'data' => [], 'raw' => $raw];
        }

        return ['ok' => true, 'data' => array_filter($data, fn ($v): bool => $v !== null && $v !== ''), 'raw' => $raw];
    }

    /**
     * 解析 whois 原始文本（public 便于单测）
     *
     * @return array<string, mixed>
     */
    public function parseRaw(string $raw): array
    {
        $out = [];

        $single = [
            'creation' => '/(?:Creation Date|Created On|Created Date|created|Registered On|Registration Time|Domain Registration Date)\s*:\s*([^\r\n]+)/i',
            'updated' => '/(?:Updated Date|Last Updated On|Last Modified|changed|Domain Last Updated Date)\s*:\s*([^\r\n]+)/i',
            'expiry' => '/(?:Registry Expiry Date|Expiry Date|Expiration Date|Expiration Time|paid-till|Expires On|Domain Expiration Date)\s*:\s*([^\r\n]+)/i',
            'registrar' => '/Registrar\s*:\s*([^\r\n]+)/i',
            'registrar_url' => '/Registrar URL\s*:\s*([^\r\n]+)/i',
            'registrar_whois' => '/Registrar WHOIS Server\s*:\s*([^\r\n]+)/i',
            'registrar_email' => '/Registrar Abuse Contact Email\s*:\s*([^\r\n]+)/i',
            'registrar_phone' => '/Registrar Abuse Contact Phone\s*:\s*([^\r\n]+)/i',
            'registrant' => '/Registrant Organization\s*:\s*([^\r\n]+)/i',
            'registrant_email' => '/Registrant Email\s*:\s*([^\r\n]+)/i',
            'dnssec' => '/DNSSEC\s*:\s*([^\r\n]+)/i',
        ];

        foreach ($single as $key => $pattern) {
            if (preg_match($pattern, $raw, $m) > 0) {
                // 行尾可能带 whois 展示模板的注释列（多个空格后的说明），一并去除
                $value = trim(preg_replace('/\s{2,}.*$/', '', $m[1]) ?? '');

                if ($value !== '' && ! str_contains(strtolower($value), 'please')) {
                    $out[$key] = mb_substr($value, 0, 200);
                }
            }
        }

        // 域名状态（可多行）
        $statuses = [];

        if (preg_match_all('/(?:Domain Status|status|State)\s*:\s*(\S+)/i', $raw, $m) > 0) {
            foreach ($m[1] as $status) {
                $statuses[] = $status.'（'.(self::STATUS_ZH[$status] ?? '见 EPP 状态码说明').'）';
            }
        }

        if ($statuses !== []) {
            $out['statuses'] = array_values(array_unique($statuses));
        }

        // 域名服务器（Name Server / nserver 变体，可多行）
        $ns = [];

        if (preg_match_all('/(?:Name Server|Nameservers|nserver|Name servers)\s*:\s*([a-z0-9.-]+)/i', $raw, $m) > 0) {
            foreach ($m[1] as $server) {
                $ns[] = strtolower(rtrim($server, '.'));
            }
        }

        if ($ns !== []) {
            $out['nameservers'] = implode(', ', array_values(array_unique($ns)));
        }

        return $out;
    }

    /**
     * RDAP 查询（结构化源，raw 缺失字段时补齐）
     *
     * @return array<string, mixed>
     */
    protected function fetchRdap(string $domain): array
    {
        try {
            $response = Http::timeout(15)->connectTimeout(8)->get('https://rdap.org/domain/'.rawurlencode($domain));

            if ($response->failed()) {
                return [];
            }

            $json = $response->json();

            if (! is_array($json)) {
                return [];
            }
        } catch (Throwable) {
            return [];
        }

        $out = [];

        // events：registration / expiration / last changed → 日期
        $map = ['registration' => 'creation', 'expiration' => 'expiry', 'last changed' => 'updated'];

        foreach (is_array($json['events'] ?? null) ? $json['events'] : [] as $event) {
            if (! is_array($event)) {
                continue;
            }

            $key = $map[Typed::string($event['eventAction'] ?? '')] ?? null;

            if ($key === null) {
                continue;
            }

            $date = Typed::stringOrNull($event['eventDate'] ?? null);

            if ($date !== null && ($out[$key] ?? null) === null) {
                $out[$key] = $date;
            }
        }

        // status[] → 中文释义
        $statuses = [];

        foreach (is_array($json['status'] ?? null) ? $json['status'] : [] as $status) {
            $status = Typed::string($status);

            if ($status !== '') {
                $statuses[] = $status.'（'.(self::STATUS_ZH[$status] ?? '见 EPP 状态码说明').'）';
            }
        }

        if ($statuses !== []) {
            $out['statuses'] = $statuses;
        }

        // secureDNS
        if (is_array($json['secureDNS'] ?? null) && isset($json['secureDNS']['delegationSigned'])) {
            $out['dnssec'] = $json['secureDNS']['delegationSigned'] ? 'signedDelegation（已签名）' : 'unsigned（未签名）';
        }

        $out = $this->mergeRdapEntities($json, $out);

        // nameservers[]
        $ns = [];

        foreach (is_array($json['nameservers'] ?? null) ? $json['nameservers'] : [] as $nameserver) {
            $ldh = strtolower(rtrim(Typed::string(is_array($nameserver) ? ($nameserver['ldhName'] ?? '') : ''), '.'));

            if ($ldh !== '') {
                $ns[] = $ldh;
            }
        }

        if ($ns !== []) {
            $out['nameservers'] = implode(', ', array_values(array_unique($ns)));
        }

        return $out;
    }

    /**
     * RDAP entities：registrar（vcard fn/url/tel/email + 嵌套 abuse）与 registrant
     *
     * @param  array<string, mixed>  $json
     * @param  array<string, mixed>  $out
     * @return array<string, mixed>
     */
    protected function mergeRdapEntities(array $json, array $out): array
    {
        foreach (is_array($json['entities'] ?? null) ? $json['entities'] : [] as $entity) {
            if (! is_array($entity)) {
                continue;
            }

            $roles = is_array($entity['roles'] ?? null) ? $entity['roles'] : [];
            $vcard = is_array($entity['vcardArray'] ?? null) ? ($entity['vcardArray'][1] ?? null) : null;

            if (in_array('registrar', $roles, true) && is_array($vcard)) {
                $out += array_filter([
                    'registrar' => $out['registrar'] ?? static::vcardValue($vcard, 'fn'),
                    'registrar_url' => $out['registrar_url'] ?? static::vcardValue($vcard, 'url'),
                    'registrar_email' => $out['registrar_email'] ?? static::vcardValue($vcard, 'email'),
                    'registrar_phone' => $out['registrar_phone'] ?? static::vcardValue($vcard, 'tel'),
                ], fn ($v): bool => $v !== null && $v !== '');

                // 嵌套 abuse 实体（常见：registrar → abuse 邮箱/电话）
                foreach (is_array($entity['entities'] ?? null) ? $entity['entities'] : [] as $child) {
                    if (is_array($child) && in_array('abuse', is_array($child['roles'] ?? null) ? $child['roles'] : [], true)) {
                        $childVcard = is_array($child['vcardArray'] ?? null) ? ($child['vcardArray'][1] ?? null) : null;

                        if (is_array($childVcard)) {
                            $out += array_filter([
                                'registrar_email' => $out['registrar_email'] ?? static::vcardValue($childVcard, 'email'),
                                'registrar_phone' => $out['registrar_phone'] ?? static::vcardValue($childVcard, 'tel'),
                            ], fn ($v): bool => $v !== null && $v !== '');
                        }
                    }
                }
            }

            if (in_array('registrant', $roles, true) && is_array($vcard)) {
                $out += array_filter([
                    'registrant' => $out['registrant'] ?? static::vcardValue($vcard, 'fn'),
                    'registrant_email' => $out['registrant_email'] ?? static::vcardValue($vcard, 'email'),
                ], fn ($v): bool => $v !== null && $v !== '');
            }
        }

        return $out;
    }

    /**
     * RFC6350 vcard 数组取属性值（vcard = [version, {}, 'fn', {}, 'text', 'Name', …]）
     */
    public static function vcardValue(array $vcard, string $key): ?string
    {
        $count = count($vcard);

        for ($i = 0; $i < $count; $i++) {
            if (is_string($vcard[$i]) && strcasecmp($vcard[$i], $key) === 0) {
                $value = $vcard[$i + 2] ?? null;

                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }

                // tel 常见 ['tel', {}, 'uri', 'tel:+1650…'] 形式
                if (is_array($value) && isset($value[3]) && is_string($value[3]) && trim($value[3]) !== '') {
                    return trim($value[3]);
                }
            }
        }

        return null;
    }

    /**
     * 任意日期串 → Y-m-d（ISO8601 / 2026.03.28 / 28-Mar-2026），失败 null
     */
    public function normalizeDate(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d{4})[.\-\/](\d{1,2})[.\-\/](\d{1,2})/', $value, $m) > 0) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        $ts = strtotime($value);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /**
     * 注册日 → 「X 年 X 个月 X 天」
     */
    public function humanAge(string $creationDate): string
    {
        try {
            $diff = (new \DateTimeImmutable($creationDate))->diff(new \DateTimeImmutable('today'));
        } catch (Throwable) {
            return '-';
        }

        $parts = [];

        if ($diff->y > 0) {
            $parts[] = $diff->y.' 年';
        }

        if ($diff->m > 0) {
            $parts[] = $diff->m.' 个月';
        }

        if ($diff->y === 0 && $diff->d > 0) {
            $parts[] = $diff->d.' 天';
        }

        return $parts === [] ? '不足 1 天' : trim(implode('', $parts));
    }

    /**
     * 过期日 → 「剩余 N 天」/「已过期 N 天」
     */
    public function remainingDays(string $expiryDate): string
    {
        try {
            $days = (int) (new \DateTimeImmutable('today'))->diff(new \DateTimeImmutable($expiryDate))->format('%r%a');
        } catch (Throwable) {
            return '-';
        }

        return $days >= 0 ? '剩余 '.$days.' 天' : '已过期 '.abs($days).' 天';
    }
}
