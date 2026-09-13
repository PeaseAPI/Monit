<?php

namespace App\Services\Seo\Tools;

use App\Services\GeoIp;
use App\Services\Seo\AuditEngine;
use App\Services\Seo\DnsResolver;
use App\Services\Seo\DomainMonitor;
use App\Services\Seo\SslInspector;
use App\Services\Seo\WhoisParser;
use App\Support\CountryNames;
use App\Support\Ip2Region;
use App\Support\Typed;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 网络与域名工具组
 */
class NetworkTools
{
    /**
     * @return array{ok: bool, error?: string, response: Response, ms: int}|array{ok: false, error: string}
     */
    protected function fetch(string $url): array
    {
        // SSRF 防护：拦截内网/环回/云元数据目标
        $blocked = AuditEngine::rejectUnsafeUrl($url);
        if ($blocked !== null) {
            return ['ok' => false, 'error' => $blocked];
        }

        try {
            $started = microtime(true);
            $response = Http::timeout(20)->withOptions(['verify' => false])->get(AuditEngine::normalizeUrl($url));
            $elapsed = (int) round((microtime(true) - $started) * 1000);

            return ['ok' => true, 'response' => $response, 'ms' => $elapsed];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }
    }

    /**
     * DNS 记录查询（对标 chinaz DNS 工具：A/AAAA/CNAME/MX/NS/TXT/SOA/PTR/SRV/CAA
     * 十种记录类型，逐条展示记录值与 TTL；PTR 输入 IP 自动转 in-addr.arpa）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function dnsLookup(array $in): array
    {
        $domain = trim(Typed::string($in['domain'] ?? ''));
        $type = strtoupper(Typed::string($in['type'] ?? 'all'));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名或 IP（PTR 查询）', 'data' => []];
        }

        $types = [
            'A' => DNS_A, 'AAAA' => DNS_AAAA, 'CNAME' => DNS_CNAME, 'MX' => DNS_MX,
            'NS' => DNS_NS, 'TXT' => DNS_TXT, 'SOA' => DNS_SOA, 'PTR' => DNS_PTR,
            'SRV' => DNS_SRV, 'CAA' => DNS_CAA,
        ];

        $labels = ['A' => 'A 记录', 'AAAA' => 'AAAA 记录', 'CNAME' => 'CNAME 记录', 'MX' => 'MX 记录',
            'NS' => 'NS 记录', 'TXT' => 'TXT 记录', 'SOA' => 'SOA 记录', 'PTR' => 'PTR 记录',
            'SRV' => 'SRV 记录', 'CAA' => 'CAA 记录'];

        $targets = $type === 'ALL' ? array_keys($types) : [$type];

        if (! isset($types[$type]) && $type !== 'ALL') {
            return ['ok' => false, 'error' => '不支持的记录类型', 'data' => []];
        }

        $data = [];
        $found = false;

        foreach ($targets as $name) {
            // PTR：IP → in-addr.arpa / ip6.arpa
            $query = $name === 'PTR' && filter_var($domain, FILTER_VALIDATE_IP) !== false
                ? static::reverseZone($domain)
                : $domain;

            if ($query === '') {
                continue;
            }

            $records = Typed::dnsRecords(@dns_get_record($query, $types[$name]));

            if ($records === []) {
                $data[$labels[$name]] = $name === 'A' && filter_var($domain, FILTER_VALIDATE_IP) === false
                    ? (gethostbyname($domain) !== $domain ? gethostbyname($domain).'（系统解析）' : '无记录')
                    : '无记录';

                continue;
            }

            $found = true;

            if (count($records) === 1) {
                $data[$labels[$name]] = static::formatDnsRecord($records[0], $name);
            } else {
                $i = 1;

                foreach ($records as $record) {
                    $data[$labels[$name].' #'.$i] = static::formatDnsRecord($record, $name);
                    $i++;
                }
            }
        }

        if (! $found && count($data) === count($targets)) {
            // 全部无记录也返回 ok（列出各类「无记录」，与 chinaz 行为一致）
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * IP → 反向解析域（in-addr.arpa / ip6.arpa）
     */
    protected static function reverseZone(string $ip): string
    {
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return '';
        }

        if (str_contains($ip, ':')) {
            $hex = bin2hex((string) $packed);

            return implode('.', array_reverse(str_split($hex))).'.ip6.arpa';
        }

        return implode('.', array_reverse(explode('.', $ip))).'.in-addr.arpa';
    }

    /**
     * 单条 DNS 记录 → 「值 (TTL xx)」展示串
     *
     * @param  array<string, mixed>  $record
     */
    protected static function formatDnsRecord(array $record, string $type): string
    {
        $ttl = (int) ($record['ttl'] ?? 0);
        $suffix = '（TTL '.$ttl.'s）';

        $value = match ($type) {
            'A' => Typed::string($record['ip'] ?? ''),
            'AAAA' => Typed::string($record['ipv6'] ?? ''),
            'CNAME' => Typed::string($record['target'] ?? ''),
            'MX' => (string) ($record['pri'] ?? '-').' '.Typed::string($record['target'] ?? ''),
            'NS' => Typed::string($record['target'] ?? ''),
            'TXT' => Typed::string($record['txt'] ?? ''),
            'SOA' => Typed::string($record['mname'] ?? '').' '.Typed::string($record['rname'] ?? '')
                .' (serial='.($record['serial'] ?? '-').' min='.($record['minimum-ttl'] ?? '-').')',
            'PTR' => Typed::string($record['target'] ?? ''),
            'SRV' => ($record['pri'] ?? '-').' '.($record['weight'] ?? '-').' '.($record['port'] ?? '-').' '.Typed::string($record['target'] ?? ''),
            'CAA' => ($record['flags'] ?? '-').' '.Typed::string($record['tag'] ?? '').' "'.Typed::string($record['value'] ?? '').'"',
            default => json_encode($record, JSON_UNESCAPED_UNICODE),
        };

        $value = trim($value);

        return $value !== '' ? $value.$suffix : '（空记录）'.$suffix;
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ipLookup(array $in): array
    {
        $ip = trim(Typed::string($in['ip'] ?? ''));

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['ok' => false, 'error' => 'IP 格式无效', 'data' => []];
        }

        // 任务 #35-9：接入本地 GeoIp（MaxMind City Lite + ip2region 中国省市级）
        // 任务 #36-9：国家显示中文名+国旗（对标 chinaz）；补运营商与 ASN——
        // 优先 ip2region 第 4 段（中国=电信/联通/移动，海外多为机构英文名），
        // 海外缺失时回退 ip-api.com（大陆可达）→ Team Cymru（海外部署场景）
        $geo = app(GeoIp::class)->lookup($ip);
        $countryCode = $geo['country_code'];

        $data = [
            'IP 版本' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? 'IPv4' : 'IPv6',
            '地址类型' => static::ipScope($ip),
            '反向解析' => Typed::nonEmpty(@gethostbyaddr($ip), '无'),
            '大洲' => static::continentName($geo['continent_code']),
            '国家/地区' => $countryCode !== null
                ? trim(CountryNames::name($countryCode, app()->getLocale()).' '.CountryNames::flag($countryCode)).' ('.$countryCode.')'
                : null,
            '省份/州' => $geo['region_name'],
            '城市' => $geo['city_name'],
            '运营商' => Ip2Region::isp($ip) ?? static::ipApiValue($ip, 'isp') ?? static::cymruAsnName($ip),
            'ASN' => static::ipApiValue($ip, 'as') ?? static::cymruAsnNumber($ip),
            '经纬度' => ($geo['latitude'] !== null && $geo['longitude'] !== null)
                ? $geo['latitude'].', '.$geo['longitude']
                : null,
            ...static::ipMathFields($ip),
        ];

        // GeoIp 库未命中（内网 IP / 本地库缺失）的键不展示，避免一排空值
        return ['ok' => true, 'data' => array_filter($data, fn ($v) => $v !== null)];
    }

    /**
     * IP 地址类型（对标 chinaz「IP转换」区块）：公网 / 私网 / 保留地址
     */
    protected static function ipScope(string $ip): string
    {
        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;

        if ($isPublic) {
            return '公网地址';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) !== false) {
            return '私网地址（内网）';
        }

        return '保留地址';
    }

    /**
     * IP 数字形式区块（对标 chinaz 数字地址：十进制/十六进制/二进制/掩码，仅 IPv4）
     *
     * @return array<string, string>
     */
    protected static function ipMathFields(string $ip): array
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return [];
        }

        $packed = @inet_pton($ip);

        if ($packed === false) {
            return [];
        }

        $long = (int) current(unpack('N', (string) $packed) ?: [0]);
        $mask = (($long & 0xFF000000) >> 24).'.'.((($long & 0x00FF0000) >> 16) & 0xFF)
            .'.'.((($long & 0x0000FF00) >> 8) & 0xFF).'.'.($long & 0xFF);

        return [
            '数字地址' => number_format($long, 0, '', ''),
            '十六进制' => '0x'.strtoupper(bin2hex((string) $packed)),
            '二进制' => substr(chunk_split(str_pad(decbin($long), 32, '0', STR_PAD_LEFT), 8, '.'), 0, -1),
            '网络掩码' => $mask,
        ];
    }

    /**
     * Team Cymru ASN 反查：IP → AS 号（origin / origin6 TXT 记录），不可达返回 null
     */
    protected static function cymruAsnNumber(string $ip): ?string
    {
        return static::cymruQuery($ip, 0);
    }

    /**
     * ip-api.com 免费查询（大陆服务器可达的 ASN/ISP 回退源——Team Cymru 的
     * DNS TXT 与 whois:43 在大陆网络普遍不可达，实测被拦截）
     *
     * 返回 as（形如「AS15169 Google LLC」）/ isp 字段；status=success 才采用，
     * 失败 / 超时（实测该源延迟抖动 1.3~5.4s+）/ 限速（免费版 45 次/分钟）
     * 静默返回 null 回退 Cymru。结果缓存：成功 7 天、失败 1 小时（IP 归属
     * 基本不变；失败短缓存避免连续外呼惩罚），缓存值包一层数组以区分
     * 「缓存了 null」与「未命中」。
     */
    protected static function ipApiValue(string $ip, string $field): ?string
    {
        $key = 'ipapi:'.md5($ip).':'.$field;
        $hit = Cache::get($key);

        if (is_array($hit) && array_key_exists('v', $hit)) {
            return Typed::stringOrNull($hit['v']);
        }

        $value = null;
        try {
            $response = Http::timeout(6)->get('http://ip-api.com/json/'.$ip, ['fields' => 'status,'.$field]);
            if ($response->successful() && Typed::string($response->json('status')) === 'success') {
                $candidate = trim(Typed::string($response->json($field)));
                $value = $candidate !== '' ? $candidate : null;
            }
        } catch (Throwable) {
            $value = null;
        }

        Cache::put($key, ['v' => $value], $value !== null ? now()->addDays(7) : now()->addHour());

        return $value;
    }

    /**
     * Team Cymru ASN 反查：IP → AS 机构名（asn.cymru.com TXT 第 2 字段）
     */
    protected static function cymruAsnName(string $ip): ?string
    {
        return static::cymruQuery($ip, 1);
    }

    /**
     * Cymru 查询统一入口：$field=0 取 AS 号，$field=1 取 AS 名
     *
     * origin TXT 形如「9808 | 222.222.0.0/16 | CN | ripencc | 2001-04-25」，
     * asn TXT 形如「AS9808 | CHINA UNICOM China Hebei Province Backbone, CN」。
     * dns_get_record 无超时参数，依赖系统 resolver 默认超时，与 dnsLookup 工具同前提。
     */
    protected static function cymruQuery(string $ip, int $field): ?string
    {
        $isV6 = str_contains($ip, ':');

        if ($isV6) {
            $nibbles = strrev(bin2hex((string) @inet_pton($ip)));
            $reverse = implode('.', str_split($nibbles));
            $zone = 'origin6.asn.cymru.com';
        } else {
            $reverse = implode('.', array_reverse(explode('.', $ip)));
            $zone = 'origin.asn.cymru.com';
        }

        if ($reverse === '') {
            return null;
        }

        $records = @dns_get_record($reverse.'.'.$zone, DNS_TXT);
        $txt = Typed::string($records[0]['txt'] ?? '');

        if ($txt === '') {
            return null;
        }

        $asn = preg_replace('/[^0-9].*$/', '', trim((string) (explode('|', $txt)[0] ?? '')));

        if ($asn === null || $asn === '') {
            return null;
        }

        if ($field === 0) {
            return 'AS'.$asn;
        }

        $asRecords = @dns_get_record($asn.'.asn.cymru.com', DNS_TXT);
        $asTxt = Typed::string($asRecords[0]['txt'] ?? '');
        $name = trim((string) (explode('|', $asTxt)[1] ?? ''));

        return $name !== '' ? $name : null;
    }

    /** 大洲两位码 → 中文名（未识别时原样返回） */
    protected static function continentName(?string $code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        return match ($code) {
            'AS' => '亚洲',
            'EU' => '欧洲',
            'NA' => '北美洲',
            'SA' => '南美洲',
            'AF' => '非洲',
            'OC' => '大洋洲',
            'AN' => '南极洲',
            default => $code,
        };
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function sslLookup(array $in): array
    {
        $host = Typed::string(preg_replace('#^https?://#', '', trim(Typed::string($in['host'] ?? ''))));

        if ($host === '') {
            return ['ok' => false, 'error' => '请输入主机名', 'data' => []];
        }

        // 复用 SslInspector（品牌 / DV-OV-EV / SAN / 剩余天数），与域名监控 monitor_ssl 同源
        $info = (new SslInspector)->inspect($host, 10);

        if ($info === null) {
            return ['ok' => false, 'error' => 'SSL 连接失败（443 端口不可达或证书握手失败）', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '证书品牌' => $info['brand'],
            '证书类型' => $info['type'],
            '颁发者' => $info['issuer'] ?? '-',
            '颁发给 (CN)' => $info['subject'] ?? '-',
            '主体组织' => $info['organization'] ?? '-',
            'SAN 域名数' => (string) count($info['san'] ?? []),
            '生效日期' => $info['valid_from'] !== null ? substr((string) $info['valid_from'], 0, 10) : '-',
            '失效日期' => $info['valid_to'] !== null ? substr((string) $info['valid_to'], 0, 10) : '-',
            '剩余天数' => (string) ($info['days_left'] ?? '-'),
        ]];
    }

    /**
     * WHOIS 全字段查询（对标 chinaz whois 页：注册/更新/过期时间、域名年龄、
     * 注册商联络、注册人、域名状态中文释义、DNSSEC + 原始记录）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function whoisLookup(array $in): array
    {
        $domain = trim(Typed::string($in['domain'] ?? ''));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $result = app(WhoisParser::class)->detailed($domain);

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? '查询失败', 'data' => [], 'text' => $result['raw'] ?? null];
        }

        return ['ok' => true, 'data' => $result['data'], 'text' => $result['raw']];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ping(array $in): array
    {
        $host = Typed::string(preg_replace('#^https?://#', '', trim(Typed::string($in['host'] ?? ''))));

        if ($host === '') {
            return ['ok' => false, 'error' => '请输入主机名', 'data' => []];
        }

        // ICMP 需 root 权限，改用 TCP 握手延迟（80/443）
        $port = str_contains($host, ':443') ? 443 : 80;
        $host = strtok($host, ':');

        $started = microtime(true);
        $socket = @fsockopen($host, $port, $errorCode, $errorString, 5);

        if ($socket === false) {
            return ['ok' => false, 'error' => "连接失败：{$errorString}", 'data' => []];
        }

        $ms = round((microtime(true) - $started) * 1000, 1);
        fclose($socket);

        return ['ok' => true, 'data' => ['目标' => "{$host}:{$port}", '延迟' => $ms.' ms']];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function reverseIpLookup(array $in): array
    {
        $ip = trim(Typed::string($in['ip'] ?? ''));

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['ok' => false, 'error' => 'IP 格式无效', 'data' => []];
        }

        $host = gethostbyaddr($ip);

        return ['ok' => $host !== $ip, 'error' => $host !== $ip ? null : '无反向记录', 'data' => ['主机名' => $host]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function domainIpLookup(array $in): array
    {
        $domain = Typed::string(preg_replace('#^https?://#', '', trim(Typed::string($in['domain'] ?? ''))));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $ipv4 = gethostbyname($domain);
        $ipv6 = Typed::dnsRecords(@dns_get_record($domain, DNS_AAAA));

        return ['ok' => $ipv4 !== $domain || $ipv6 !== [], 'data' => [
            'IPv4' => $ipv4 !== $domain ? $ipv4 : '无',
            'IPv6' => $ipv6 !== [] ? ($ipv6[0]['ipv6'] ?? '无') : '无',
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function statusChecker(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $response = $result['response'];

        return ['ok' => true, 'data' => [
            '状态码' => $response->status(),
            '状态' => $response->successful() ? '正常' : '异常',
            '响应时间' => $result['ms'].' ms',
            '最终 URL' => (string) ($response->effectiveUri() ?? '-'),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function redirectChecker(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $response = $result['response'];

        return ['ok' => true, 'data' => [
            '重定向次数' => $response->handlerStats()['redirect_count'] ?? 0,
            '最终 URL' => (string) ($response->effectiveUri() ?? '-'),
            '状态码' => $response->status(),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function redirectTrace(array $in): array
    {
        $url = AuditEngine::normalizeUrl(Typed::string($in['url'] ?? ''));
        $chain = [];
        $current = $url;
        $visited = 0;

        while ($visited < 10) {
            // SSRF 防护：重定向的每一跳都可能是内网地址（Location 由目标站
            // 可控），逐跳校验，命中即中断并回显原因
            $blocked = AuditEngine::rejectUnsafeUrl($current);
            if ($blocked !== null) {
                return ['ok' => false, 'error' => $blocked, 'data' => []];
            }

            try {
                $response = Http::timeout(15)->withOptions(['verify' => false, 'allow_redirects' => false])->get($current);
            } catch (Throwable $e) {
                return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
            }

            $location = $response->header('Location');
            $chain[] = $response->status().' '.$current;

            if ($response->status() < 300 || $response->status() >= 400 || $location === '') {
                break;
            }

            $current = trim($location);
            $visited++;
        }

        return ['ok' => true, 'data' => [], 'text' => implode("\n → ", $chain)];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ttfbChecker(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '首字节时间' => $result['ms'].' ms',
            '评级' => $result['ms'] < 200 ? '优秀' : ($result['ms'] < 500 ? '良好' : '待优化'),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function hostingChecker(array $in): array
    {
        $host = Typed::string(parse_url(AuditEngine::normalizeUrl(Typed::string($in['url'] ?? '')), PHP_URL_HOST));
        $ip = $host !== '' ? gethostbyname($host) : '';

        if ($ip === $host || $ip === '') {
            return ['ok' => false, 'error' => '域名解析失败', 'data' => []];
        }

        $reverse = Typed::nonEmpty(gethostbyaddr($ip), '');

        return ['ok' => true, 'data' => array_filter([
            'IP 地址' => $ip,
            '反向解析' => $reverse,
            '推测托管商' => preg_match('/([a-z0-9-]+)\.(com|net|org|cn|io)$/i', $reverse, $m) > 0 ? $m[1] : '无法识别',
        ], fn (string $v): bool => $v !== '')];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function headersLookup(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $data = [];
        foreach ($result['response']->headers() as $name => $values) {
            $data[$name] = implode(', ', array_map(fn ($v) => Typed::string($v), (array) $values));
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function http2Checker(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '协议版本' => $result['response']->handlerStats()['http_version'] ?? '无法探测',
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function brotliChecker(array $in): array
    {
        // SSRF 防护：拦截内网/环回/云元数据目标
        $blocked = AuditEngine::rejectUnsafeUrl(AuditEngine::normalizeUrl(Typed::string($in['url'] ?? '')));
        if ($blocked !== null) {
            return ['ok' => false, 'error' => $blocked, 'data' => []];
        }

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Accept-Encoding' => 'gzip, br'])
                ->get(AuditEngine::normalizeUrl(Typed::string($in['url'] ?? '')));
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        $encoding = strtolower($response->header('Content-Encoding'));

        return ['ok' => true, 'data' => [
            'Content-Encoding' => Typed::nonEmpty($encoding, '无'),
            'Brotli' => str_contains($encoding, 'br') ? '已启用' : '未启用',
            'Gzip' => str_contains($encoding, 'gzip') ? '已启用' : '未启用',
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function googleCacheChecker(array $in): array
    {
        $url = AuditEngine::normalizeUrl(Typed::string($in['url'] ?? ''));

        try {
            $response = Http::timeout(20)->get('https://webcache.googleusercontent.com/search?q=cache:'.urlencode($url));
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '缓存状态' => $response->status() === 200 ? '存在缓存快照' : '无缓存快照',
            'HTTP 状态码' => $response->status(),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function idnConverter(array $in): array
    {
        $domain = trim(Typed::string($in['domain'] ?? ''));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'Punycode' => Typed::nonEmpty(idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII), '转换失败'),
            'Unicode' => Typed::nonEmpty(idn_to_utf8($domain, IDNA_NONTRANSITIONAL_TO_UNICODE), '转换失败'),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function textExtractor(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $html = $result['response']->body();
        $text = preg_replace('#<(script|style|noscript)[^>]*>.*?</\1>#is', ' ', $html) ?? '';
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8')) ?? '');

        return ['ok' => true, 'data' => ['提取字数' => mb_strlen($text)], 'text' => mb_substr($text, 0, 5000)];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function pageSizeChecker(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $bytes = strlen($result['response']->body());

        return ['ok' => true, 'data' => [
            '页面大小' => number_format($bytes / 1024, 1).' KB',
            '响应时间' => $result['ms'].' ms',
        ]];
    }

    /**
     * 多节点 DNS 检测（对标 chinaz 多线多地 DNS：公共 DNS 解析一致性 + IP 占比统计）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function dnsPropagation(array $in): array
    {
        $domain = rtrim(trim(Typed::string($in['domain'] ?? '')), '.');

        if ($domain === '' || filter_var($domain, FILTER_VALIDATE_DOMAIN) === false) {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $nodes = [
            '114 DNS（南京）' => '114.114.114.114',
            '阿里 DNS（杭州）' => '223.5.5.5',
            '腾讯 DNSPod（深圳）' => '119.29.29.29',
            'Google DNS' => '8.8.8.8',
            'Cloudflare DNS' => '1.1.1.1',
        ];

        $resolver = new DnsResolver;
        $data = [];
        $votes = [];

        foreach ($nodes as $label => $server) {
            $result = $resolver->query($domain, $server, 'A', 2000);

            if (! $result['ok']) {
                $data['【'.$label.'】'] = $result['error'] ?? '查询失败';

                continue;
            }

            if (($result['nxdomain'] ?? false) === true) {
                $data['【'.$label.'】'] = 'NXDOMAIN（域名不存在）';

                continue;
            }

            $values = array_map(fn (array $a): string => Typed::string($a['value']), array_filter(
                $result['answers'],
                fn (array $a): bool => $a['type'] === 'A'
            ));

            if ($values === []) {
                $data['【'.$label.'】'] = '无 A 记录';

                continue;
            }

            $data['【'.$label.'】'] = implode(' | ', $values);

            foreach ($values as $value) {
                $votes[$value] = ($votes[$value] ?? 0) + 1;
            }
        }

        if ($votes !== []) {
            arsort($votes);
            $total = count($nodes);
            $data['IP 占比'] = implode('，', array_map(
                fn (string $ip, int $count): string => $ip.'（'.round($count / $total * 100).'%）',
                array_keys($votes),
                $votes
            ));

            $distinct = count($votes);
            $data['解析一致性'] = $distinct === 1
                ? '完全一致（'.$distinct.' 个唯一 IP）'
                : '存在差异（'.$distinct.' 个唯一 IP，可能使用 CDN/负载均衡）';
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * 子域名查询（对标 chinaz 子域名查询：crt.sh 证书透明日志 + 常见前缀 DNS 探测）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function subdomainScanner(array $in): array
    {
        $domain = strtolower(rtrim(trim(Typed::string($in['domain'] ?? '')), '.'));
        $domain = explode('/', (string) preg_replace('/^https?:\/\//', '', $domain))[0];

        if (! preg_match('/^(?!-)[a-z0-9-]+(\.[a-z0-9-]+)+$/i', $domain)) {
            return ['ok' => false, 'error' => '请输入合法域名', 'data' => []];
        }

        $found = [];

        // 源 1：crt.sh 证书透明日志（免费公开，可能较慢/超时，容错）
        try {
            $response = Http::timeout(12)->get('https://crt.sh/', ['q' => '%.'.$domain, 'output' => 'json']);

            foreach (Typed::arr($response->json()) as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                foreach (preg_split('/[\s,]+/', Typed::string($entry['name_value'] ?? '')) ?: [] as $name) {
                    $name = strtolower(rtrim(trim($name), '.'));

                    if ($name === $domain || str_ends_with($name, '.'.$domain)) {
                        $found[$name] = true;
                    }
                }
            }
        } catch (Throwable) {
            // crt.sh 不可达时仅用前缀探测
        }

        // 源 2：常见前缀 DNS 探测（解析成功即收录）
        foreach (['www', 'mail', 'smtp', 'ftp', 'api', 'admin', 'app', 'm', 'mobile', 'dev', 'test', 'staging',
            'blog', 'shop', 'store', 'pay', 'cdn', 'img', 'static', 'assets', 'ns1', 'ns2', 'vpn', 'oa', 'git',
            'docs', 'help', 'support', 'portal', 'sso', 'login', 'bbs', 'forum', 'wap', 'h5', 'download', 'dl'] as $prefix) {
            $host = $prefix.'.'.$domain;

            if (gethostbyname($host) !== $host) {
                $found[$host] = true;
            }
        }

        $list = array_keys($found);
        sort($list);
        $list = array_slice($list, 0, 100);

        $data = ['主域名' => $domain, '子域名数量' => (string) count($list)];

        foreach ($list as $i => $host) {
            $ip = gethostbyname($host);

            $data['子域名 #'.($i + 1)] = $host.($ip !== $host ? ' → '.$ip : '');
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * 服务器信息（对标 chinaz SEO 综合查询「服务器信息」区块：协议/压缩/压缩比/
     * 页面类型/服务器类型/网页大小）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function websiteServerInfo(array $in): array
    {
        $url = Typed::string($in['url'] ?? '');

        $blocked = AuditEngine::rejectUnsafeUrl($url);

        if ($blocked !== null) {
            return ['ok' => false, 'error' => $blocked, 'data' => []];
        }

        try {
            $started = microtime(true);
            $response = Http::timeout(20)->withHeaders(['Accept-Encoding' => 'gzip, deflate, br'])
                ->withOptions(['verify' => false])
                ->get(AuditEngine::normalizeUrl($url));
            $elapsed = (int) round((microtime(true) - $started) * 1000);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        $body = $response->body();
        $rawSize = (int) ($response->header('Content-Length') ?: 0);
        $unpackedSize = strlen($body);
        $encoding = strtolower($response->header('Content-Encoding'));

        $data = [
            '状态码' => $response->status(),
            '协议类型' => static::httpVersion($response),
            '服务器类型' => Typed::nonEmpty($response->header('Server'), '未披露'),
            '程序支持' => Typed::nonEmpty($response->header('X-Powered-By'), '未披露'),
            '页面类型' => Typed::nonEmpty($response->header('Content-Type'), '-'),
            '是否压缩' => $encoding !== '' ? '是（'.strtoupper($encoding).'）' : '否',
        ];

        if ($rawSize > 0 && $unpackedSize > 0) {
            $data['原网页大小'] = number_format($unpackedSize / 1024, 2).' KB';
            $data['压缩后大小'] = number_format($rawSize / 1024, 2).' KB';
            $data['压缩比'] = round($rawSize / $unpackedSize * 100, 2).'%';
        } else {
            $data['页面大小'] = number_format($unpackedSize / 1024, 2).' KB';
        }

        $data['响应时间'] = $elapsed.' ms';

        return ['ok' => true, 'data' => $data];
    }

    /**
     * curl handlerStats → 可读 HTTP 版本（拿不到时退回 '-'）
     */
    protected static function httpVersion(Response $response): string
    {
        $stats = $response->handlerStats();
        $version = is_array($stats) ? ($stats['http_version'] ?? null) : null;

        return match ($version !== null ? (int) $version : 0) {
            3 => 'HTTP/3',
            2 => 'HTTP/2',
            1 => 'HTTP/1.1',
            0 => 'HTTP/1.0',
            default => '-',
        };
    }

    /**
     * 端口扫描（对标 chinaz 端口扫描：常见端口非阻塞并发探测，1~2 秒内完成）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function portScanner(array $in): array
    {
        $host = rtrim(trim(Typed::string($in['host'] ?? '')), '.');
        $host = (string) preg_replace('#^[a-z]+://#i', '', $host);
        $host = explode('/', $host)[0];
        $host = explode(':', $host)[0];

        if ($host === '') {
            return ['ok' => false, 'error' => '请输入主机名或 IP', 'data' => []];
        }

        // SSRF 防护：内网/环回目标拦截（复用审计引擎，域名解析后判定）
        $blocked = AuditEngine::rejectUnsafeUrl('http://'.$host);

        if ($blocked !== null) {
            return ['ok' => false, 'error' => $blocked, 'data' => []];
        }

        // 域名 → IP（扫描目标必须是 IP；gethostbyname 失败时原样返回）
        $ip = filter_var($host, FILTER_VALIDATE_IP) !== false ? $host : gethostbyname($host);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['ok' => false, 'error' => '主机名无法解析', 'data' => []];
        }

        $common = [21 => 'FTP', 22 => 'SSH', 23 => 'Telnet', 25 => 'SMTP', 53 => 'DNS', 80 => 'HTTP',
            110 => 'POP3', 143 => 'IMAP', 443 => 'HTTPS', 465 => 'SMTPS', 587 => 'SMTP(提交)',
            993 => 'IMAPS', 995 => 'POP3S', 1433 => 'MSSQL', 3306 => 'MySQL', 3389 => 'RDP',
            5432 => 'PostgreSQL', 6379 => 'Redis', 8080 => 'HTTP 备用', 8443 => 'HTTPS 备用',
            8888 => '面板常用', 27017 => 'MongoDB'];

        $data = ['目标' => $host.'（'.$ip.'）'];
        $open = static::probePorts($ip, array_keys($common));

        if ($open === []) {
            $data['开放端口'] = '未发现常见端口开放';
        } else {
            $i = 1;

            foreach ($open as $port) {
                $data['开放端口 #'.$i] = $port.'（'.$common[$port].'）';
                $i++;
            }
        }

        $risky = [6379, 3306, 27017, 1433, 5432];
        $data['高危提醒'] = count(array_intersect($risky, $open)) > 0
            ? '检测到数据库/缓存端口对公网开放，建议配置防火墙或访问控制'
            : '未检测到数据库端口对公网开放';

        return ['ok' => true, 'data' => $data];
    }

    /**
     * 非阻塞并发 TCP 探测：全部 socket 异步 connect + stream_select 等待可写，
     * 连接被拒（RST）会立即可读 EOF；可写且未 EOF 视为开放。总窗口 2.5 秒。
     *
     * @param  list<int>  $ports
     * @return list<int>
     */
    protected static function probePorts(string $ip, array $ports): array
    {
        $sockets = [];
        $open = [];

        foreach ($ports as $port) {
            $sock = @stream_socket_client(
                'tcp://'.$ip.':'.$port,
                $errno,
                $errstr,
                0.05,
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
            );

            if (is_resource($sock)) {
                stream_set_blocking($sock, false);
                $sockets[(int) $sock] = ['sock' => $sock, 'port' => $port];
            }
        }

        $deadline = microtime(true) + 2.5;

        while ($sockets !== [] && microtime(true) < $deadline) {
            $read = $write = [];
            $except = null;

            foreach ($sockets as $entry) {
                $read[] = $entry['sock'];
                $write[] = $entry['sock'];
            }

            if (@stream_select($read, $write, $except, 0, 250000) === false || ($read === [] && $write === [])) {
                continue;
            }

            foreach (array_unique(array_merge($read, $write)) as $active) {
                $id = (int) $active;

                if (! isset($sockets[$id])) {
                    continue;
                }

                $entry = $sockets[$id];
                $meta = @stream_get_meta_data($active);

                if (($meta['eof'] ?? false) || ($meta['timed_out'] ?? false)) {
                    fclose($entry['sock']);
                    unset($sockets[$id]);

                    continue;
                }

                $open[] = $entry['port'];
                fclose($entry['sock']);
                unset($sockets[$id]);
            }
        }

        foreach ($sockets as $entry) {
            @fclose($entry['sock']);
        }

        sort($open);

        return $open;
    }

    /**
     * IP WHOIS（对标 chinaz IP WHOIS：RDAP 查询网段/机构/国家/注册信息）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ipWhois(array $in): array
    {
        $ip = trim(Typed::string($in['ip'] ?? ''));

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['ok' => false, 'error' => 'IP 格式无效', 'data' => []];
        }

        try {
            $response = Http::timeout(15)->connectTimeout(8)->get('https://rdap.org/ip/'.$ip);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        if ($response->failed()) {
            return ['ok' => false, 'error' => 'RDAP 查询失败（HTTP '.$response->status().'）', 'data' => []];
        }

        $json = $response->json();

        if (! is_array($json)) {
            return ['ok' => false, 'error' => 'RDAP 响应格式异常', 'data' => []];
        }

        $data = [
            'IP' => $ip,
            '网段名称' => Typed::nonEmpty(Typed::string($json['name'] ?? ''), '-'),
            '类型' => Typed::nonEmpty(Typed::string($json['type'] ?? ''), '-'),
            '国家' => Typed::nonEmpty(Typed::string($json['country'] ?? ''), '-'),
            '注册机构' => '-',
        ];

        // CIDR 范围
        $start = Typed::string($json['startAddress'] ?? '');
        $end = Typed::string($json['endAddress'] ?? '');

        if ($start !== '' && $end !== '') {
            $data['IP 段'] = $start.' ~ '.$end;
        }

        $handle = Typed::string($json['handle'] ?? '');

        if ($handle !== '') {
            $data['网段句柄'] = $handle;
        }

        // 注册机构：entities[] roles 含 registrant 的 vcard fn
        foreach (is_array($json['entities'] ?? null) ? $json['entities'] : [] as $entity) {
            if (! is_array($entity) || $data['注册机构'] !== '-') {
                continue;
            }

            $roles = is_array($entity['roles'] ?? null) ? $entity['roles'] : [];

            if (in_array('registrant', $roles, true)) {
                $vcard = is_array($entity['vcardArray'] ?? null) ? ($entity['vcardArray'][1] ?? null) : null;
                $name = is_array($vcard) ? WhoisParser::vcardValue($vcard, 'fn') : null;

                if ($name !== null) {
                    $data['注册机构'] = $name;
                }
            }
        }

        // 事件
        foreach (is_array($json['events'] ?? null) ? $json['events'] : [] as $event) {
            if (! is_array($event)) {
                continue;
            }

            $action = Typed::string($event['eventAction'] ?? '');
            $date = Typed::string($event['eventDate'] ?? '');

            if ($action === 'registration' && $date !== '') {
                $data['注册时间'] = substr($date, 0, 10);
            }

            if ($action === 'last changed' && $date !== '') {
                $data['最后变更'] = substr($date, 0, 10);
            }
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * IP 批量查询（对标 chinaz IP 批量：多行输入，逐行归属地/运营商摘要）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ipBatchLookup(array $in): array
    {
        $lines = preg_split('/[\r\n,;]+/', Typed::string($in['text'] ?? '')) ?: [];
        $targets = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // 支持直接给域名：先解析成 IP
            $targets[] = filter_var($line, FILTER_VALIDATE_IP) !== false
                ? $line
                : gethostbyname($line);
        }

        $targets = array_values(array_unique(array_slice($targets, 0, 30)));

        if ($targets === []) {
            return ['ok' => false, 'error' => '请输入至少一个 IP 或域名（每行一个）', 'data' => []];
        }

        $geoService = app(GeoIp::class);
        $data = ['查询数量' => (string) count($targets)];
        $i = 1;

        foreach ($targets as $target) {
            if (filter_var($target, FILTER_VALIDATE_IP) === false) {
                $data['#'.$i] = $target.' → 解析失败';
                $i++;

                continue;
            }

            $geo = $geoService->lookup($target);
            $location = implode(' ', array_filter([
                $geo['country_code'] !== null ? CountryNames::name($geo['country_code'], 'zh_CN') : null,
                $geo['region_name'],
                $geo['city_name'],
            ]));

            $isp = Ip2Region::isp($target) ?? static::ipApiValue($target, 'isp');
            $data['#'.$i] = $target.' → '.Typed::nonEmpty($location, '未知').'，运营商：'.Typed::nonEmpty($isp, '未知');
            $i++;
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * 蜘蛛真伪验证（对标 chinaz「百度真假蜘蛛」：PTR + 正向解析 + 主机名归属验证）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function spiderCheck(array $in): array
    {
        $ip = trim(Typed::string($in['ip'] ?? ''));
        $ua = Typed::string($in['user_agent'] ?? '');

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return ['ok' => false, 'error' => '请输入合法的蜘蛛 IP', 'data' => []];
        }

        // UA 串识别蜘蛛家族
        $families = [
            'Baiduspider' => ['baidu.com', 'baidu.jp', 'baidu.cn'],
            'Googlebot' => ['googlebot.com', 'google.com'],
            'bingbot' => ['search.msn.com'],
            'Sogou spider' => ['sogou.com'],
            '360Spider' => ['so.com', 'qihu.com'],
            'YandexBot' => ['yandex.com', 'yandex.ru', 'yandex.net'],
        ];

        $claimed = null;

        foreach ($families as $needle => $suffixes) {
            if (str_contains($ua, $needle)) {
                $claimed = ['name' => $needle, 'suffixes' => $suffixes];

                break;
            }
        }

        $data = ['IP' => $ip, 'UA' => Typed::nonEmpty($ua, '未提供'), 'UA 平台' => '-'];
        $data['UA 平台'] = $claimed === null ? '未识别（UA 不含已知蜘蛛标识）' : $claimed['name'];

        // PTR 反解 + 正向验证
        $ptr = @gethostbyaddr($ip);
        $data['反向解析 (PTR)'] = $ptr !== $ip ? $ptr : '无 PTR 记录';

        if ($ptr === $ip) {
            $data['验证结论'] = '无法验证（无 PTR 记录）';

            return ['ok' => true, 'data' => $data];
        }

        $forward = gethostbyname($ptr);
        $data['正向解析'] = $forward !== $ptr ? $forward.'（'.($forward === $ip ? '与原 IP 一致' : '与原 IP 不一致').'）' : '解析失败';
        $data['PTR 一致性'] = $forward === $ip ? '一致（真实蜘蛛的必要条件）' : '不一致（可疑）';

        if ($claimed !== null) {
            $hostOk = false;

            foreach ($claimed['suffixes'] as $suffix) {
                if (str_ends_with(strtolower($ptr), $suffix)) {
                    $hostOk = true;

                    break;
                }
            }

            $data['主机名归属'] = $ptr.'（'.($hostOk ? '属于 '.$claimed['name'].' 官方域' : '不属于 '.$claimed['name'].' 官方域').'）';
            $data['验证结论'] = $hostOk && $forward === $ip
                ? '✅ 真实蜘蛛（'.$claimed['name'].'）'
                : ($hostOk ? '⚠️ 官方域但 PTR 正反不一致，需进一步核实' : '❌ 疑似伪装蜘蛛');
        } else {
            $data['验证结论'] = $forward === $ip ? '⚠️ PTR 一致但未提供蜘蛛 UA，仅确认主机名真实性' : '❌ PTR 正反不一致（常见于攻击源）';
        }

        return ['ok' => true, 'data' => $data];
    }

    /**
     * CDN 检测（对标 chinaz CDN 查询：CNAME 特征 + 响应头特征识别厂商）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function cdnChecker(array $in): array
    {
        $host = strtolower(rtrim(trim(Typed::string($in['host'] ?? '')), '.'));
        $host = (string) preg_replace('#^[a-z]+://#i', '', $host);
        $host = explode('/', $host)[0];

        if ($host === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $cname = Typed::dnsRecords(@dns_get_record($host, DNS_CNAME));
        $cnameTarget = Typed::string($cname[0]['target'] ?? '');

        $server = '';
        $headers = [];

        $blocked = AuditEngine::rejectUnsafeUrl('http://'.$host);

        if ($blocked === null) {
            try {
                $response = Http::timeout(12)->get('http://'.$host);
                $server = strtolower(Typed::string($response->header('Server')));
                $headers = array_change_key_case($response->headers()->all(), CASE_LOWER);
            } catch (Throwable) {
                // HTTP 失败不影响 CNAME 特征判定
            }
        }

        $result = static::cdnSignature($cnameTarget, $server, $headers);

        $data = [
            '目标域名' => $host,
            'CNAME' => Typed::nonEmpty($cnameTarget, '无'),
            '是否使用 CDN' => $result['is_cdn'],
        ];

        if ($result['provider'] !== null) {
            $data['CDN 厂商'] = $result['provider'];
        }

        if ($result['evidence'] !== []) {
            $data['判定依据'] = implode('；', $result['evidence']);
        }

        $data['Server 响应头'] = Typed::nonEmpty($server, '未披露');

        return ['ok' => true, 'data' => $data];
    }

    /**
     * CDN 特征判定：CNAME 后缀库 + 响应头特征库
     *
     * @param  array<string, mixed>  $headers
     * @return array{provider: ?string, is_cdn: string, evidence: list<string>}
     */
    protected static function cdnSignature(string $cnameTarget, string $server, array $headers): array
    {
        // CNAME 特征库（主流 CDN 提供商解析别名后缀）
        $cnameSignatures = [
            'Cloudflare' => ['cloudflare.net', 'cloudflare.com'],
            'AWS CloudFront' => ['cloudfront.net'],
            'Akamai' => ['akamaiedge.net', 'akamai.net', 'akamaistream.net', 'edgekey.net', 'edgesuite.net'],
            'Fastly' => ['fastly.net', 'fastlylb.net'],
            'Google Cloud CDN' => ['googlehosted.com', 'googleusercontent.com'],
            'Azure CDN' => ['azureedge.net', 'msecnd.net', 'azurefd.net'],
            '阿里云 CDN' => ['kunlun', 'alicdn.com', 'alikunlun.com'],
            '腾讯云 CDN' => ['cdn.dnsv1.com', 'dnsv1.com', 'tencentyun.com', 'tcdn.qq.com'],
            '华为云 CDN' => ['cdnhwc1.com', 'cdnhwc2.com', 'cdnhwc3.com', 'hwclouds'],
            '百度云 CDN' => ['jomodns.com', 'bdcdn.net', 'baidubce.com'],
            '又拍云' => ['aicdn.com'],
            '七牛云' => ['qiniudns.com', 'qiniuapi.com', 'qbox.me'],
            '网宿 CDN' => ['wscdns.com', 'cdn20.com', 'lxdns.com'],
            '白山云 CDN' => ['baishancloud.com', 'bsclink.cn'],
            '金山云 CDN' => ['kscdn.com', 'ksyuncdn.com'],
        ];

        $evidence = [];

        if ($cnameTarget !== '') {
            $lower = strtolower($cnameTarget);

            foreach ($cnameSignatures as $name => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($lower, $needle)) {
                        $evidence[] = 'CNAME='.$cnameTarget;

                        return ['provider' => $name, 'is_cdn' => '是', 'evidence' => $evidence];
                    }
                }
            }
        }

        // 响应头特征（带厂商的）
        $headerHits = [
            'cf-ray' => 'Cloudflare',
            'cf-cache-status' => 'Cloudflare',
            'x-amz-cf-id' => 'AWS CloudFront',
            'x-akamai-transformed' => 'Akamai',
            'x-served-by' => 'Fastly',
            'x-vercel-id' => 'Vercel',
        ];

        foreach ($headerHits as $header => $vendor) {
            if (isset($headers[$header])) {
                $evidence[] = '响应头 '.$header;

                return ['provider' => $vendor, 'is_cdn' => '是', 'evidence' => $evidence];
            }
        }

        if (str_contains($server, 'cloudflare')) {
            $evidence[] = 'Server: cloudflare';

            return ['provider' => 'Cloudflare', 'is_cdn' => '是', 'evidence' => $evidence];
        }

        return [
            'provider' => null,
            'is_cdn' => $cnameTarget !== '' ? '可能（存在 CNAME 但未匹配已知厂商）' : '未检出',
            'evidence' => $evidence,
        ];
    }

    /**
     * 网页源码查看（对标 chinaz 查看源代码：抓取并展示原始 HTML）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function sourceViewer(array $in): array
    {
        $result = $this->fetch(Typed::string($in['url'] ?? ''));

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? null, 'data' => []];
        }

        $html = $result['response']->body();
        $lines = substr_count($html, "\n") + 1;

        return [
            'ok' => true,
            'data' => [
                '状态码' => $result['response']->status(),
                '页面类型' => Typed::nonEmpty($result['response']->header('Content-Type'), '-'),
                '源码大小' => number_format(strlen($html) / 1024, 2).' KB',
                '行数' => $lines,
                '响应时间' => $result['ms'].' ms',
            ],
            'text' => mb_substr($html, 0, 100000).($lines > 0 && strlen($html) > 100000 ? "\n\n……（源码过长已截断展示前 100KB）" : ''),
        ];
    }
}
