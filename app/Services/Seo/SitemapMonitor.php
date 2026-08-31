<?php

namespace App\Services\Seo;

use App\Models\Website;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Sitemap 监控：递归解析（sitemap index 深度上限 3）+ 变更 diff
 */
class SitemapMonitor
{
    /**
     * 拉取并展开 sitemap 全部 URL
     *
     * @return array{ok:bool, urls:array<string>, error?:string}
     */
    public function fetch(string $sitemapUrl, int $depth = 0): array
    {
        if ($depth > 3) {
            return ['ok' => true, 'urls' => []];
        }

        try {
            $response = Http::timeout(20)->get($sitemapUrl);
        } catch (Throwable $e) {
            return ['ok' => false, 'urls' => [], 'error' => '请求失败：'.mb_substr($e->getMessage(), 0, 120)];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'urls' => [], 'error' => 'HTTP '.$response->status()];
        }

        $xml = simplexml_load_string((string) $response->body());

        if ($xml === false) {
            return ['ok' => false, 'urls' => [], 'error' => 'XML 解析失败'];
        }

        $urls = [];

        // sitemap index：递归展开
        if ($xml->getName() === 'sitemapindex') {
            foreach ($xml->sitemap as $item) {
                $child = (string) ($item->loc ?? '');

                if ($child !== '') {
                    $urls = array_merge($urls, $this->fetch($child, $depth + 1)['urls']);
                }
            }

            return ['ok' => true, 'urls' => array_values(array_unique($urls))];
        }

        foreach ($xml->url as $item) {
            $loc = trim((string) ($item->loc ?? ''));

            if ($loc !== '') {
                $urls[] = $loc;
            }
        }

        return ['ok' => true, 'urls' => array_values(array_unique($urls))];
    }

    /**
     * 检查网站 sitemap 并对比上次快照
     *
     * @return array{changed:bool, added:array, removed:array, total:int, error:?string}
     */
    public function check(Website $website): array
    {
        $sitemapUrl = $website->seo_sitemap_url ?: $website->scheme.'://'.$website->host.'/sitemap.xml';

        $result = $this->fetch($sitemapUrl);

        if (! $result['ok']) {
            $website->update(['seo_sitemap_checked_at' => now()]);

            return ['changed' => false, 'added' => [], 'removed' => [], 'total' => 0, 'error' => $result['error'] ?? '未知错误'];
        }

        $urls = $result['urls'];
        $hash = md5(implode("\n", $urls));

        $previous = $website->seo_sitemap_urls_hash;
        $changed = $previous !== null && $previous !== $hash;

        $added = [];
        $removed = [];

        if ($changed) {
            // 上次 URL 集合存在 settings 快照中（首检只记录不告警）
            $lastUrls = (array) ($website->settings['seo_sitemap_urls'] ?? []);

            if ($lastUrls !== []) {
                $added = array_values(array_diff($urls, $lastUrls));
                $removed = array_values(array_diff($lastUrls, $urls));
            }
        }

        $website->update([
            'seo_sitemap_urls_hash' => $hash,
            'seo_sitemap_checked_at' => now(),
            'settings' => array_merge((array) $website->settings, ['seo_sitemap_urls' => array_slice($urls, 0, 2000)]),
        ]);

        return [
            'changed' => $changed,
            'added' => $added,
            'removed' => $removed,
            'total' => count($urls),
            'error' => null,
        ];
    }
}
