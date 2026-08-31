<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;
use App\Services\Seo\AuditTestRegistry;

/**
 * 可索引与元信息测试组（seo 类别）
 */
class MetaTests
{
    /** @return array<string, string> 测试键 => 方法名 */
    public function handles(): array
    {
        return [
            'title' => 'title',
            'meta_description' => 'metaDescription',
            'h1' => 'h1',
            'meta_keywords' => 'metaKeywords',
            'other_headings' => 'otherHeadings',
            'language' => 'language',
            'meta_charset' => 'metaCharset',
            'meta_viewport' => 'metaViewport',
            'meta_refresh' => 'metaRefresh',
            'canonical' => 'canonical',
            'opengraph' => 'opengraph',
            'schemas' => 'schemas',
            'favicon' => 'favicon',
            'not_found' => 'notFound',
            'robots' => 'robots',
            'meta_robots' => 'metaRobots',
            'header_robots' => 'headerRobots',
            'is_seo_friendly_url' => 'seoFriendlyUrl',
            'noindex_images' => 'noindexImages',
            'gsc_is_indexed' => 'gscIsIndexed',
            'gsc_coverage' => 'gscCoverage',
            'ahrefs_domain_rating' => 'ahrefsDomainRating',
            'page_rank' => 'pageRank',
            'bing_indexed' => 'bingIndexed',
            'yandex_indexed' => 'yandexIndexed',
        ];
    }

    public function title(AuditContext $c): array
    {
        $title = trim((string) $c->dom()->getElementsByTagName('title')->item(0)?->textContent);
        $length = mb_strlen($title);
        $min = (int) AuditTestRegistry::threshold('title_min', 10);
        $max = (int) AuditTestRegistry::threshold('title_max', 60);

        return [
            'passed' => $length >= $min && $length <= $max,
            'value' => $length === 0 ? '缺失' : (string) $length,
            'detail' => $title !== '' ? mb_substr($title, 0, 120) : '',
        ];
    }

    public function metaDescription(AuditContext $c): array
    {
        $length = mb_strlen((string) ($c->meta('description') ?? ''));
        $min = (int) AuditTestRegistry::threshold('description_min', 50);
        $max = (int) AuditTestRegistry::threshold('description_max', 160);

        return [
            'passed' => $length >= $min && $length <= $max,
            'value' => $length === 0 ? '缺失' : (string) $length,
        ];
    }

    public function h1(AuditContext $c): array
    {
        $count = $c->dom()->getElementsByTagName('h1')->length;

        return [
            'passed' => $count === 1,
            'value' => (string) $count,
        ];
    }

    public function metaKeywords(AuditContext $c): array
    {
        $keywords = trim((string) ($c->meta('keywords') ?? ''));

        return [
            'passed' => mb_strlen($keywords) > 0,
            'value' => $keywords === '' ? '缺失' : mb_substr($keywords, 0, 100),
        ];
    }

    public function otherHeadings(AuditContext $c): array
    {
        $count = $c->dom()->getElementsByTagName('h2')->length
            + $c->dom()->getElementsByTagName('h3')->length;

        return [
            'passed' => $count > 0,
            'value' => (string) $count,
        ];
    }

    public function language(AuditContext $c): array
    {
        $lang = (string) $c->dom()->documentElement->getAttribute('lang');

        return [
            'passed' => $lang !== '',
            'value' => $lang !== '' ? $lang : '未声明',
        ];
    }

    public function metaCharset(AuditContext $c): array
    {
        $charset = (string) ($c->meta('charset') ?? '');

        if ($charset === '') {
            // HTTP Content-Type 头声明亦可
            $contentType = (string) ($c->header('content-type') ?? '');
            $charset = str_contains($contentType, 'charset=')
                ? trim((string) preg_replace('/.*charset=([^\s;]+).*/i', '$1', $contentType))
                : '';
        }

        return [
            'passed' => $charset !== '',
            'value' => $charset !== '' ? $charset : '未声明',
        ];
    }

    public function metaViewport(AuditContext $c): array
    {
        $viewport = (string) ($c->meta('viewport') ?? '');

        return [
            'passed' => $viewport !== '',
            'value' => $viewport !== '' ? $viewport : '缺失',
        ];
    }

    public function metaRefresh(AuditContext $c): array
    {
        $refresh = (string) ($c->meta('refresh') ?? '');

        // meta refresh 存在即视为对搜索引擎不友好
        return [
            'passed' => $refresh === '',
            'value' => $refresh === '' ? '无' : $refresh,
        ];
    }

    public function canonical(AuditContext $c): array
    {
        $canonical = '';
        foreach ($c->dom()->getElementsByTagName('link') as $link) {
            if (strtolower((string) $link->getAttribute('rel')) === 'canonical') {
                $canonical = $link->getAttribute('href');

                break;
            }
        }

        return [
            'passed' => $canonical !== '',
            'value' => $canonical !== '' ? mb_substr($canonical, 0, 200) : '缺失',
        ];
    }

    public function opengraph(AuditContext $c): array
    {
        $count = 0;
        foreach ($c->dom()->getElementsByTagName('meta') as $meta) {
            if (str_starts_with((string) $meta->getAttribute('property'), 'og:')) {
                $count++;
            }
        }

        return [
            'passed' => $count >= 3,
            'value' => (string) $count,
        ];
    }

    public function schemas(AuditContext $c): array
    {
        preg_match_all('/application\/ld\+json/i', $c->html, $matches);

        return [
            'passed' => count($matches[0] ?? []) > 0,
            'value' => (string) count($matches[0] ?? []),
        ];
    }

    public function favicon(AuditContext $c): array
    {
        $found = false;
        foreach ($c->dom()->getElementsByTagName('link') as $link) {
            if (str_contains(strtolower((string) $link->getAttribute('rel')), 'icon')) {
                $found = true;

                break;
            }
        }

        return [
            'passed' => $found,
            'value' => $found ? '已声明' : '缺失',
        ];
    }

    /**
     * 软 404 探测：状态码 4xx/5xx 或正文异常短
     */
    public function notFound(AuditContext $c): array
    {
        $ok = $c->statusCode < 400 && mb_strlen($c->bodyText()) > 80;

        return [
            'passed' => $ok,
            'value' => 'HTTP '.$c->statusCode,
        ];
    }

    public function robots(AuditContext $c): array
    {
        $exists = (bool) ($c->extra['robots_exists'] ?? false);

        return [
            'passed' => $exists,
            'value' => $exists ? '存在' : '缺失',
        ];
    }

    public function metaRobots(AuditContext $c): array
    {
        $robots = strtolower((string) ($c->meta('robots') ?? ''));
        $blocked = str_contains($robots, 'noindex') || str_contains($robots, 'none');

        return [
            'passed' => ! $blocked,
            'value' => $robots === '' ? '未设置' : $robots,
        ];
    }

    public function headerRobots(AuditContext $c): array
    {
        $robots = strtolower((string) ($c->header('x-robots-tag') ?? ''));

        return [
            'passed' => ! str_contains($robots, 'noindex'),
            'value' => $robots === '' ? '未设置' : $robots,
        ];
    }

    public function seoFriendlyUrl(AuditContext $c): array
    {
        $path = (string) parse_url($c->url, PHP_URL_PATH);

        $friendly = ! preg_match('/[A-Z]/', $path)
            && ! str_contains($path, '_')
            && mb_strlen($path) <= 115
            && substr_count($path, '/') <= 5;

        return [
            'passed' => $friendly,
            'value' => $path === '' ? '/' : mb_substr($path, 0, 120),
        ];
    }

    /**
     * 图片检索引流：og:image 覆盖视为友好
     */
    public function noindexImages(AuditContext $c): array
    {
        $ogImage = (string) ($c->meta('og:image') ?? '');

        return [
            'passed' => $ogImage !== '',
            'value' => $ogImage !== '' ? '已提供' : '缺失',
        ];
    }

    /*
    |------ 外部条件项（requires 对应设置配置后才会执行）------
    */

    public function gscIsIndexed(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'Google Search Console 凭据尚未配置'];
    }

    public function gscCoverage(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'Google Search Console 凭据尚未配置'];
    }

    public function ahrefsDomainRating(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'Ahrefs API Key 尚未配置'];
    }

    public function pageRank(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'PageRank API Key 尚未配置'];
    }

    public function bingIndexed(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'Bing API Key 尚未配置'];
    }

    public function yandexIndexed(AuditContext $c): array
    {
        return ['passed' => false, 'value' => '未接入', 'detail' => 'Yandex API Key 尚未配置'];
    }
}
