<?php

namespace App\Services\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordRank;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;

/**
 * 关键词排名跟踪服务
 *
 * 数据源双模式（自托管零成本起步）：
 * - 自动：后台配置 seo.serpapi_api_key 后，按 check_interval 定时查 SERP 并匹配目标站 host
 * - 手动：用户在别处查得排名后录入快照（source=manual），平台负责趋势记录与变化提醒
 */
class RankTracker
{
    /**
     * SerpApi 是否已配置
     */
    public static function configured(): bool
    {
        $key = Settings::get('seo.serpapi_api_key');

        return is_string($key) && trim($key) !== '';
    }

    /**
     * 查询一个关键词的当前排名并落快照
     *
     * @return SeoKeywordRank 新快照（position 为 null 表示未在结果页找到目标站）
     */
    public function check(SeoKeyword $keyword): SeoKeywordRank
    {
        $host = $keyword->website_id
            ? strtolower((string) $keyword->website?->host)
            : '';

        if ($host === '') {
            $host = static::hostOfTarget($keyword->target_url);
        }

        $results = $this->fetchOrganicResults($keyword);

        $position = null;
        $urlFound = null;

        foreach ($results as $index => $row) {
            $link = (string) ($row['link'] ?? '');

            if ($link !== '' && static::sameRegistrableHost($link, $host)) {
                $position = $index + 1;
                $urlFound = $link;

                break;
            }
        }

        return $this->record($keyword, $position, $urlFound, 'auto');
    }

    /**
     * 写入排名快照并刷新关键词聚合（last/previous/best）
     */
    public function record(SeoKeyword $keyword, ?int $position, ?string $urlFound = null, string $source = 'manual'): SeoKeywordRank
    {
        $rank = SeoKeywordRank::create([
            'seo_keyword_id' => $keyword->seo_keyword_id,
            'position' => $position,
            'url_found' => $urlFound !== null ? mb_substr($urlFound, 0, 2048) : null,
            'source' => $source,
            'checked_at' => now(),
            'created_at' => now(),
        ]);

        $keyword->fill([
            'previous_position' => $keyword->last_position,
            'last_position' => $position,
            'last_checked_at' => now(),
        ]);

        if ($position !== null && ($keyword->best_position === null || $position < $keyword->best_position)) {
            $keyword->best_position = $position;
        }

        $keyword->save();

        return $rank;
    }

    /**
     * SerpApi search.json → organic_results
     */
    protected function fetchOrganicResults(SeoKeyword $keyword): array
    {
        if (! static::configured()) {
            throw new \RuntimeException('serpapi_not_configured');
        }

        $engine = match ($keyword->search_engine) {
            'bing' => 'bing',
            'baidu' => 'baidu',
            default => 'google',
        };

        $response = Http::timeout(30)->asJson()->get('https://serpapi.com/search.json', [
            'api_key' => trim((string) Settings::get('seo.serpapi_api_key')),
            'engine' => $engine,
            'q' => $keyword->keyword,
            'device' => $keyword->device ?: 'desktop',
            'hl' => $keyword->locale ?: 'zh-CN',
            'gl' => static::localeToRegion($keyword->locale),
            'num' => 100,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('serpapi_request_failed: '.$response->status());
        }

        return array_values((array) ($response->json('organic_results') ?? []));
    }

    protected static function hostOfTarget(?string $url): string
    {
        if (! $url) {
            return '';
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        return strtolower(preg_replace('/^www\./i', '', $host) ?: $host);
    }

    /**
     * 比较两个 URL 的 host（去 www），用于 SERP 结果匹配
     */
    protected static function sameRegistrableHost(string $resultUrl, string $host): bool
    {
        if ($host === '') {
            return false;
        }

        $resultHost = static::hostOfTarget($resultUrl);

        return $resultHost === $host
            || str_ends_with($resultHost, '.'.$host)
            || str_ends_with($host, '.'.$resultHost);
    }

    protected static function localeToRegion(string $locale): string
    {
        $parts = explode('-', str_replace('_', '-', $locale));

        return strtoupper(end($parts) ?: 'CN');
    }
}
