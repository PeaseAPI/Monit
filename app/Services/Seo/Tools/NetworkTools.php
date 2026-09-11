<?php

namespace App\Services\Seo\Tools;

use App\Services\Seo\AuditEngine;
use App\Services\Seo\DomainMonitor;
use App\Support\Typed;
use Illuminate\Http\Client\Response;
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
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function dnsLookup(array $in): array
    {
        $domain = trim(Typed::string($in['domain'] ?? ''));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $ip = gethostbyname($domain);

        $data = ['A 记录' => $ip !== $domain ? $ip : '未解析'];
        $mx = @dns_get_record($domain, DNS_MX) ?: [];
        $data['MX 记录'] = $mx ? implode(', ', array_column($mx, 'target')) : '无';
        $ns = @dns_get_record($domain, DNS_NS) ?: [];
        $data['NS 记录'] = $ns ? implode(', ', array_column($ns, 'target')) : '无';
        $txt = @dns_get_record($domain, DNS_TXT) ?: [];
        $data['TXT 记录'] = $txt ? implode(' | ', array_column($txt, 'txt')) : '无';

        return ['ok' => true, 'data' => $data];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function ipLookup(array $in): array
    {
        $ip = trim(Typed::string($in['ip'] ?? ''));

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['ok' => false, 'error' => 'IP 格式无效', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '反向解析' => gethostbyaddr($ip) ?: '无',
            '版本' => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'IPv4' : 'IPv6',
        ]];
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

        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false]]);

        $socket = @stream_socket_client("ssl://{$host}:443", $errorCode, $errorString, 10, STREAM_CLIENT_CONNECT, $context);

        if ($socket === false) {
            return ['ok' => false, 'error' => 'SSL 连接失败（443 端口不可达或证书握手失败）', 'data' => []];
        }

        $params = stream_context_get_params($socket);
        fclose($socket);

        $certResource = data_get($params, 'options.ssl.peer_certificate');

        if (! is_string($certResource)) {
            return ['ok' => false, 'error' => '未捕获到证书', 'data' => []];
        }

        $parsed = openssl_x509_parse($certResource);
        if ($parsed === false) {
            return ['ok' => false, 'error' => '证书解析失败', 'data' => []];
        }

        $validTo = (int) ($parsed['validTo_time_t'] ?? 0);

        return ['ok' => true, 'data' => [
            '颁发给' => data_get($parsed, 'subject.CN') ?? '-',
            '颁发者' => data_get($parsed, 'issuer.O') ?? (data_get($parsed, 'issuer.CN') ?? '-'),
            '生效日期' => date('Y-m-d', (int) ($parsed['validFrom_time_t'] ?? 0)),
            '失效日期' => date('Y-m-d', $validTo),
            '剩余天数' => (string) max(0, (int) floor(($validTo - time()) / 86400)),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function whoisLookup(array $in): array
    {
        $domain = trim(Typed::string($in['domain'] ?? ''));

        if ($domain === '') {
            return ['ok' => false, 'error' => '请输入域名', 'data' => []];
        }

        $result = app(DomainMonitor::class)->whois($domain);

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? '查询失败', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '到期日期' => $result['expiration_date'] ?? '-',
            '注册商' => $result['registrar'] ?? '-',
            '域名服务器' => implode(', ', $result['nameservers'] ?? []) ?: '-',
        ]];
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

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
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
        $ipv6 = @dns_get_record($domain, DNS_AAAA) ?: [];

        return ['ok' => $ipv4 !== $domain || $ipv6 !== [], 'data' => array_filter([
            'IPv4' => $ipv4 !== $domain ? $ipv4 : '无',
            'IPv6' => $ipv6 ? $ipv6[0]['ipv6'] ?? '无' : '无',
        ])];
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

        $reverse = gethostbyaddr($ip) ?: '';

        return ['ok' => true, 'data' => array_filter([
            'IP 地址' => $ip,
            '反向解析' => $reverse,
            '推测托管商' => preg_match('/([a-z0-9-]+)\.(com|net|org|cn|io)$/i', $reverse, $m) ? $m[1] : '无法识别',
        ])];
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
            $data[$name] = implode(', ', (array) $values);
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

        $encoding = strtolower((string) $response->header('Content-Encoding'));

        return ['ok' => true, 'data' => [
            'Content-Encoding' => $encoding ?: '无',
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
            'Punycode' => idn_to_ascii($domain, IDNA_NONTRANSITIONAL_TO_ASCII) ?: '转换失败',
            'Unicode' => idn_to_utf8($domain, IDNA_NONTRANSITIONAL_TO_UNICODE) ?: '转换失败',
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

        $html = (string) $result['response']->body();
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

        $bytes = strlen((string) $result['response']->body());

        return ['ok' => true, 'data' => [
            '页面大小' => number_format($bytes / 1024, 1).' KB',
            '响应时间' => $result['ms'].' ms',
        ]];
    }
}
