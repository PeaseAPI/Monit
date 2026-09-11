<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;

/**
 * 其它测试组（misc + mixed_content 类别）
 */
class MiscTests
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
            'image_alt' => 'imageAlt',
            'doctype' => 'doctype',
            'sitemap' => 'sitemap',
            'mixed_content' => 'mixedContent',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function imageAlt(AuditContext $c): array
    {
        $images = $c->dom()->getElementsByTagName('img');

        if ($images->length === 0) {
            return ['passed' => true, 'value' => '0'];
        }

        $missing = 0;
        foreach ($images as $img) {
            if (trim($img->getAttribute('alt')) === '') {
                $missing++;
            }
        }

        return [
            'passed' => $missing === 0,
            'value' => $missing.' / '.$images->length,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function doctype(AuditContext $c): array
    {
        $has = stripos($c->html, '<!DOCTYPE html>') !== false;

        return [
            'passed' => $has,
            'value' => $has ? 'HTML5' : '-',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sitemap(AuditContext $c): array
    {
        $exists = (bool) ($c->extra['sitemap_exists'] ?? false);

        return [
            'passed' => $exists,
            'value' => $exists ? '1' : '0',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function mixedContent(AuditContext $c): array
    {
        $count = $c->insecureResourceCount();

        return [
            'passed' => $count === 0,
            'value' => (string) $count,
        ];
    }
}
