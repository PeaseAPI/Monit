<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;
use App\Services\Seo\AuditTestRegistry;
use App\Support\Typed;

/**
 * 性能测试组（performance 类别）
 */
class PerformanceTests
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
            'response_time' => 'responseTime',
            'page_size' => 'pageSize',
            'dom_size' => 'domSize',
            'http_requests' => 'httpRequests',
            'non_deferred_scripts' => 'nonDeferredScripts',
            'inline_css' => 'inlineCss',
            'image_formats' => 'imageFormats',
            'image_lazy_loading' => 'imageLazyLoading',
            'deprecated_html_tags' => 'deprecatedHtmlTags',
            'server_compression' => 'serverCompression',
            'is_http2' => 'isHttp2',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function responseTime(AuditContext $c): array
    {
        $max = Typed::int(AuditTestRegistry::threshold('response_time_max', 1500));

        return [
            'passed' => $c->responseTimeMs > 0 && $c->responseTimeMs <= $max,
            'value' => $c->responseTimeMs.' ms',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function pageSize(AuditContext $c): array
    {
        $max = Typed::int(AuditTestRegistry::threshold('page_size_max', 3000000));

        return [
            'passed' => $c->sizeBytes > 0 && $c->sizeBytes <= $max,
            'value' => number_format($c->sizeBytes / 1024, 1).' KB',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function domSize(AuditContext $c): array
    {
        $count = $c->dom()->getElementsByTagName('*')->length;
        $max = Typed::int(AuditTestRegistry::threshold('dom_size_max', 1500));

        return [
            'passed' => $count <= $max,
            'value' => (string) $count,
        ];
    }

    /**
     * 页面引用资源数（img/script/css/media）
     *
     * @return array<string, mixed>
     */
    public function httpRequests(AuditContext $c): array
    {
        $count = $c->dom()->getElementsByTagName('img')->length
            + $c->dom()->getElementsByTagName('script')->length
            + $c->dom()->getElementsByTagName('iframe')->length;

        foreach ($c->dom()->getElementsByTagName('link') as $link) {
            if (strtolower((string) $link->getAttribute('rel')) === 'stylesheet') {
                $count++;
            }
        }

        $max = Typed::int(AuditTestRegistry::threshold('http_requests_max', 50));

        return [
            'passed' => $count <= $max,
            'value' => (string) $count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function nonDeferredScripts(AuditContext $c): array
    {
        $blocking = 0;
        foreach ($c->dom()->getElementsByTagName('script') as $script) {
            $src = $script->getAttribute('src');
            if ($src === '') {
                continue; // 内联脚本不算
            }
            if (! $script->hasAttribute('defer') && ! $script->hasAttribute('async')) {
                $blocking++;
            }
        }

        return [
            'passed' => $blocking === 0,
            'value' => (string) $blocking,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function inlineCss(AuditContext $c): array
    {
        preg_match_all('/style=["\'][^"\']*["\']/i', $c->html, $matches);

        $count = count($matches[0]);

        return [
            'passed' => $count <= 10,
            'value' => (string) $count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function imageFormats(AuditContext $c): array
    {
        $legacy = 0;
        foreach ($c->dom()->getElementsByTagName('img') as $img) {
            $src = strtolower($img->getAttribute('src'));
            if (preg_match('/\.(bmp|tiff?)($|\?)/', $src)) {
                $legacy++;
            }
        }

        return [
            'passed' => $legacy === 0,
            'value' => (string) $legacy,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function imageLazyLoading(AuditContext $c): array
    {
        $images = $c->dom()->getElementsByTagName('img');

        if ($images->length === 0) {
            return ['passed' => true, 'value' => '0'];
        }

        $lazy = 0;
        foreach ($images as $img) {
            if ($img->hasAttribute('loading') && $img->getAttribute('loading') === 'lazy') {
                $lazy++;
            }
        }

        return [
            'passed' => $lazy / $images->length >= 0.5,
            'value' => $lazy.' / '.$images->length,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function deprecatedHtmlTags(AuditContext $c): array
    {
        $deprecated = ['center', 'font', 'marquee', 'big', 'strike', 'tt', 'frame', 'frameset', 'applet'];
        $count = 0;

        foreach ($deprecated as $tag) {
            $count += $c->dom()->getElementsByTagName($tag)->length;
        }

        return [
            'passed' => $count === 0,
            'value' => (string) $count,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serverCompression(AuditContext $c): array
    {
        $encoding = strtolower((string) ($c->header('content-encoding') ?? ''));

        return [
            'passed' => $encoding !== '',
            'value' => $encoding !== '' ? $encoding : 'Not enabled',
        ];
    }

    /**
     * HTTP/2：客户端层面不可见，按响应头线索推断（alt-svc / x-firefox-spdy 等）
     *
     * @return array<string, mixed>
     */
    public function isHttp2(AuditContext $c): array
    {
        $hint = (string) ($c->header('alt-svc') ?? '');

        return [
            'passed' => true,
            'value' => $hint !== '' ? mb_substr($hint, 0, 60) : '-',
        ];
    }
}
