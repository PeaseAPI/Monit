<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;

/**
 * 安全测试组（security 类别）
 */
class SecurityTests
{
    public function handles(): array
    {
        return [
            'is_https' => 'isHttps',
            'is_ssl_valid' => 'isSslValid',
            'hsts' => 'hsts',
            'csp' => 'csp',
            'unsafe_forms' => 'unsafeForms',
            'unsafe_external_links' => 'unsafeExternalLinks',
            'safe_browsing' => 'safeBrowsing',
            'header_server' => 'headerServer',
            'spf' => 'spf',
            'referrer_policy' => 'referrerPolicy',
        ];
    }

    public function isHttps(AuditContext $c): array
    {
        return [
            'passed' => $c->scheme === 'https',
            'value' => strtoupper($c->scheme),
        ];
    }

    public function isSslValid(AuditContext $c): array
    {
        if ($c->scheme !== 'https') {
            return ['passed' => false, 'value' => 'Not HTTPS'];
        }

        $ssl = $c->sslInfo ?? [];

        $valid = (bool) ($ssl['valid'] ?? false);

        return [
            'passed' => $valid,
            'value' => $valid ? ($ssl['valid_to'] ?? 'Valid') : 'Invalid or unverified',
        ];
    }

    public function hsts(AuditContext $c): array
    {
        $hsts = (string) ($c->header('strict-transport-security') ?? '');

        return [
            'passed' => $hsts !== '',
            'value' => $hsts !== '' ? mb_substr($hsts, 0, 80) : 'Not enabled',
        ];
    }

    public function csp(AuditContext $c): array
    {
        $csp = (string) ($c->header('content-security-policy') ?? '');

        return [
            'passed' => $csp !== '',
            'value' => $csp !== '' ? mb_substr($csp, 0, 80) : 'Not enabled',
        ];
    }

    public function unsafeForms(AuditContext $c): array
    {
        $unsafe = 0;
        foreach ($c->dom()->getElementsByTagName('form') as $form) {
            $action = strtolower($form->getAttribute('action'));

            if ($action !== '' && str_starts_with($action, 'http://')) {
                $unsafe++;
            }
        }

        return [
            'passed' => $unsafe === 0,
            'value' => (string) $unsafe,
        ];
    }

    /**
     * target=_blank 外链须带 rel=noopener/noreferrer
     */
    public function unsafeExternalLinks(AuditContext $c): array
    {
        $unsafe = 0;

        foreach ($c->dom()->getElementsByTagName('a') as $a) {
            if ($a->getAttribute('target') !== '_blank') {
                continue;
            }

            $rel = strtolower($a->getAttribute('rel'));

            if (! str_contains($rel, 'noopener') && ! str_contains($rel, 'noreferrer')) {
                $unsafe++;
            }
        }

        return [
            'passed' => $unsafe === 0,
            'value' => (string) $unsafe,
        ];
    }

    public function safeBrowsing(AuditContext $c): array
    {
        // 条件项：后台开启 safe_browsing 后执行（默认信任，标记未接入）
        return ['passed' => true, 'value' => '-'];
    }

    public function headerServer(AuditContext $c): array
    {
        $server = (string) ($c->header('server') ?? '');

        // 暴露服务器版本细节视为轻微风险
        return [
            'passed' => $server === '' || ! preg_match('/\d+\.\d+/', $server),
            'value' => $server === '' ? 'Hidden' : mb_substr($server, 0, 60),
        ];
    }

    public function spf(AuditContext $c): array
    {
        $records = @dns_get_record($c->host, DNS_TXT) ?: [];

        $has = false;
        foreach ((array) $records as $record) {
            if (str_contains(strtolower((string) ($record['txt'] ?? '')), 'v=spf1')) {
                $has = true;

                break;
            }
        }

        return [
            'passed' => $has,
            'value' => $has ? '1' : '0',
        ];
    }

    public function referrerPolicy(AuditContext $c): array
    {
        // meta referrer 或 Referrer-Policy 头任一即可
        $meta = (string) ($c->meta('referrer') ?? '');
        $header = (string) ($c->header('referrer-policy') ?? '');

        return [
            'passed' => $meta !== '' || $header !== '',
            'value' => $meta !== '' ? $meta : ($header !== '' ? $header : '-'),
        ];
    }
}
