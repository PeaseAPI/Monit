<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;

/**
 * 链接结构测试组（links 类别）
 */
class LinkTests
{
    /**
     * @return array<string, string>
     */
    /**
     * @return array<string, string>
     */
    public function handles(): array
    {
        return [
            'internal_links' => 'internalLinks',
            'external_links' => 'externalLinks',
            'in_page_links' => 'inPageLinks',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function internalLinks(AuditContext $c): array
    {
        $count = 0;
        foreach ($c->dom()->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            $host = (string) parse_url($href, PHP_URL_HOST);

            if ($host === '' || strcasecmp((string) preg_replace('/^www\./', '', $host), $c->host) === 0) {
                $count++;
            }
        }

        return [
            'passed' => $count > 0,
            'value' => (string) $count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function externalLinks(AuditContext $c): array
    {
        $count = 0;
        foreach ($c->dom()->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));
            $host = (string) parse_url($href, PHP_URL_HOST);

            if ($host !== '' && strcasecmp((string) preg_replace('/^www\./', '', $host), $c->host) !== 0) {
                $count++;
            }
        }

        return [
            'passed' => true,
            'value' => (string) $count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inPageLinks(AuditContext $c): array
    {
        $count = $c->dom()->getElementsByTagName('a')->length;

        return [
            'passed' => $count > 0,
            'value' => (string) $count,
        ];
    }
}
