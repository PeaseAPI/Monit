<?php

namespace App\Services\Seo;

use App\Support\Typed;
use Throwable;

/**
 * SSL 证书探测（任务 #35-6）
 *
 * 443 端口 stream 直连抓取对端证书并解析，输出：
 * - 品牌（issuer O 字段映射，覆盖 Let's Encrypt / ZeroSSL / DigiCert / TrustAsia 等）
 * - 证书类型 DV/OV/EV（按证书策略 OID 2.23.140.1.2.x 判定；无 OID 时按主体组织有无推断）
 * - 主体 CN / 主体组织（OV/EV）/ SAN 域名列表 / 有效期 / 剩余天数
 *
 * 域名监控 monitor_ssl 落库与工具中心 sslLookup 共用此服务
 */
class SslInspector
{
    /** @var array<string, string> issuer O 字段（小写包含匹配）→ 显示品牌 */
    protected const BRANDS = [
        "let's encrypt" => "Let's Encrypt",
        'zerossl' => 'ZeroSSL',
        'google trust services' => 'Google Trust Services',
        'digicert' => 'DigiCert',
        'globalsign' => 'GlobalSign',
        'sectigo' => 'Sectigo',
        'godaddy' => 'GoDaddy',
        'trustasia' => 'TrustAsia',
        'geotrust' => 'GeoTrust',
        'rapidssl' => 'RapidSSL',
        'thawte' => 'thawte',
        'cfca' => 'CFCA',
        'vtrust' => 'vTrus',
        'ssl.com' => 'SSL.com',
        'entrust' => 'Entrust',
        'buypass' => 'Buypass',
        'actalis' => 'Actalis',
        'certum' => 'Certum',
        'swisssign' => 'SwissSign',
        'amazon' => 'Amazon Trust Services',
        'microsoft azure' => 'Microsoft Azure',
        'tencent' => 'TrustAsia (腾讯云)',
        'alibaba' => 'Alibaba Cloud',
        'wotrus' => 'WoTrus',
        'unizone' => 'UniZone',
        'hans azg' => 'Hans Azg',
    ];

    /**
     * 探测主机 443 证书；任何失败（连接/握手/解析）静默返回 null
     *
     * @return array<string, mixed>|null
     */
    public function inspect(string $host, int $timeout = 8): ?array
    {
        $host = strtolower((string) preg_replace('#^https?://#', '', rtrim(trim($host), '/')));
        $host = (string) preg_replace('/[\/:].*$/', '', $host);

        if ($host === '' || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return null;
        }

        $context = stream_context_create(['ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false, // 监控场景：自签/过期证书也要能取到并展示
            'verify_peer_name' => false,
            'SNI_enabled' => true,
            'peer_name' => $host,
        ]]);

        try {
            $socket = @stream_socket_client("ssl://{$host}:443", $errorCode, $errorString, $timeout, STREAM_CLIENT_CONNECT, $context);
            if ($socket === false) {
                return null;
            }
            $cert = data_get(stream_context_get_params($socket), 'options.ssl.peer_certificate');
            fclose($socket);
            // PHP 8 返回 OpenSSLCertificate 对象（旧代码 is_string() 判断恒失败 → 永远「未捕获到证书」）
            if (! ($cert instanceof \OpenSSLCertificate || is_string($cert))) {
                return null;
            }
            $parsed = openssl_x509_parse($cert);
            if ($parsed === false) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return $this->normalize($parsed);
    }

    /**
     * openssl_x509_parse 结果 → 监控/工具统一展示载荷
     *
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    protected function normalize(array $p): array
    {
        $issuerOrg = $this->firstIssuer($p, 'O') ?? $this->firstIssuer($p, 'OU') ?? $this->firstIssuer($p, 'CN');
        $subjectCn = Typed::stringOrNull(data_get($p, 'subject.CN'));
        $organization = Typed::stringOrNull(data_get($p, 'subject.O'));

        $policies = Typed::string(data_get($p, 'extensions.certificatePolicies'));
        $type = null;
        if ($policies !== '') {
            if (str_contains($policies, '2.23.140.1.2.3')) {
                $type = 'EV';
            } elseif (str_contains($policies, '2.23.140.1.2.2')) {
                $type = 'OV';
            } elseif (str_contains($policies, '2.23.140.1.2.1')) {
                $type = 'DV';
            }
        }
        // CA/B 论坛策略 OID 缺失时按主体组织推断：有 O → OV，否则 DV
        $type ??= ($organization !== null ? 'OV' : 'DV');

        $validFrom = Typed::int($p['validFrom_time_t'] ?? 0);
        $validTo = Typed::int($p['validTo_time_t'] ?? 0);

        $san = [];
        foreach (preg_split('/\s*,\s*/', (string) (data_get($p, 'extensions.subjectAltName') ?? '')) ?: [] as $entry) {
            if (preg_match('/^(?:DNS|IP Address):(.+)$/i', trim($entry), $m)) {
                $san[] = strtolower($m[1]);
            }
        }

        return [
            'brand' => $this->detectBrand(Typed::string($issuerOrg)),
            'type' => $type,
            'issuer' => $issuerOrg,
            'subject' => $subjectCn,
            'organization' => $organization,
            'san' => array_values(array_unique($san)),
            'valid_from' => $validFrom > 0 ? date('Y-m-d H:i:s', $validFrom) : null,
            'valid_to' => $validTo > 0 ? date('Y-m-d H:i:s', $validTo) : null,
            'days_left' => $validTo > 0 ? max(0, (int) floor(($validTo - time()) / 86400)) : null,
        ];
    }

    /**
     * issuer 数组中某字段的第一个值（CA 证书链上含多个 O 时取签发 CA 的）
     *
     * @param  array<string, mixed>  $p
     */
    protected function firstIssuer(array $p, string $field): ?string
    {
        $value = data_get($p, 'issuer.'.$field.'.0') ?? data_get($p, 'issuer.'.$field);

        return Typed::stringOrNull($value);
    }

    protected function detectBrand(string $issuerOrg): string
    {
        $haystack = strtolower($issuerOrg);

        foreach (self::BRANDS as $needle => $brand) {
            if (str_contains($haystack, $needle)) {
                return $brand;
            }
        }

        return $issuerOrg ?: '—';
    }
}
