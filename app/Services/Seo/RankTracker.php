<?php

namespace App\Services\Seo;

use App\Models\SeoKeyword;
use App\Models\SeoKeywordRank;
use App\Support\Settings;
use App\Support\Typed;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 关键词排名跟踪服务
 *
 * 数据源双模式（自托管零成本起步）：
 * - SerpApi 已配置：官方 API 查询（Google/Bing/Baidu 全引擎、100 条深翻页）
 * - 未配置：内置 SERP 抓取兜底（任务 #36-7）——Bing（www/cn 双通道）与百度
 *   HTML 解析提取自然结果链接序列；Google 大陆服务器通常不可达，直连失败时
 *   抛 serp_scrape_unreachable:google 由调用方提示（不静默记录「未找到」）
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
        $host = ($keyword->website_id !== 0) ? strtolower((string) $keyword->website?->host)
            : '';

        if ($host === '') {
            $host = static::hostOfTarget($keyword->target_url);
        }

        // 任务 #35-7：既不关联网站也无目标 URL 的关键词无法匹配排名，
        // 抛异常让调用方计为失败（而非误记一条「未找到」快照污染趋势）
        if ($host === '') {
            throw new \RuntimeException('keyword_target_host_missing');
        }

        $results = static::configured()
            ? $this->fetchOrganicResults($keyword)
            : $this->scrapeOrganicResults($keyword);

        $position = null;
        $urlFound = null;

        foreach ($results as $index => $row) {
            $link = Typed::string($row['link'] ?? '');

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
     *
     * @return array<int, array<string, mixed>>
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
            'api_key' => trim(Typed::string(Settings::get('seo.serpapi_api_key'))),
            'engine' => $engine,
            'q' => $keyword->keyword,
            'device' => $keyword->device ?? 'desktop',
            'hl' => $keyword->locale ?? 'zh-CN',
            'gl' => static::localeToRegion($keyword->locale),
            'num' => 100,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('serpapi_request_failed: '.$response->status());
        }

        /** @var array<int, array<string, mixed>> $organic */
        $organic = (array) ($response->json('organic_results') ?? []);

        return array_values($organic);
    }

    /**
     * 内置 SERP 抓取（未配置 SerpApi 时的零成本兜底，任务 #36-7）
     *
     * 返回与 SerpApi organic_results 同构的 ['link' => url] 列表，复用同一 host 匹配逻辑。
     * 抓取仅取首页 50 条：排名 >50 记为「未找到」，自托管场景够用；反爬命中
     * （百度安全验证 / Bing 挑战页 / 超时）按引擎抛 serp_scrape_* 异常，由调用方
     * （store / refresh / cron）计为失败提示，绝不静默记一条「未找到」污染趋势。
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scrapeOrganicResults(SeoKeyword $keyword): array
    {
        return match ($keyword->search_engine) {
            'bing' => $this->scrapeBing($keyword),
            'baidu' => $this->scrapeBaidu($keyword),
            default => $this->scrapeGoogle($keyword),
        };
    }

    /**
     * Bing：www.bing.com 与 cn.bing.com 双通道（大陆访问 www 常被 302 至 cn，
     * 任一通道解析出结果即可），解析 b_algo 列表项的首个标题链接
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scrapeBing(SeoKeyword $keyword): array
    {
        $q = rawurlencode(Typed::string($keyword->keyword));
        $channels = [
            'https://www.bing.com/search?q='.$q.'&count=50&first=1&setlang='.rawurlencode(static::localeTag($keyword->locale)),
            'https://cn.bing.com/search?q='.$q.'&count=50&first=1',
        ];

        foreach ($channels as $channel) {
            try {
                $response = $this->scrapeHttp($keyword->locale)->get($channel);
            } catch (Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $links = static::extractBingLinks($response->body());

            if ($links !== []) {
                return $links;
            }
        }

        throw new \RuntimeException('serp_scrape_failed:bing');
    }

    /**
     * 百度：结果容器的 mu= 属性即真实落地 URL，出现顺序即自然排名
     * （/link?url= 跳转壳不含 mu；百度自有子域如 zhidao 也是真实结果，保留）
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scrapeBaidu(SeoKeyword $keyword): array
    {
        $q = rawurlencode(Typed::string($keyword->keyword));

        try {
            $response = $this->scrapeHttp($keyword->locale)
                ->withHeaders(['Referer' => 'https://www.baidu.com/'])
                ->get('https://www.baidu.com/s?wd='.$q.'&rn=50&ie=utf-8');
        } catch (Throwable $e) {
            throw new \RuntimeException('serp_scrape_failed:baidu:'.mb_substr($e->getMessage(), 0, 120));
        }

        if (! $response->successful()) {
            throw new \RuntimeException('serp_scrape_failed:baidu:'.$response->status());
        }

        $html = $response->body();

        // 反爬识别：安全验证页没有 mu= 结果容器，直接判失败避免空结果误报
        if (str_contains($html, '百度安全验证') || str_contains($html, 'wappass.baidu.com')) {
            throw new \RuntimeException('serp_scrape_blocked:baidu');
        }

        return static::extractBaiduLinks($html);
    }

    /**
     * Google：大陆服务器通常不可达，直连失败抛专用异常（提示可配置 SerpApi）；
     * 解析 curl UA 场景的 /url?q= 简版结果与直接外链，过滤 Google 自有域
     *
     * @return array<int, array<string, mixed>>
     */
    protected function scrapeGoogle(SeoKeyword $keyword): array
    {
        $q = rawurlencode(Typed::string($keyword->keyword));

        try {
            $response = $this->scrapeHttp($keyword->locale)
                ->get('https://www.google.com/search?q='.$q.'&num=50&hl='.rawurlencode(static::localeTag($keyword->locale)).'&pws=0');
        } catch (Throwable) {
            throw new \RuntimeException('serp_scrape_unreachable:google');
        }

        if (! $response->successful()) {
            throw new \RuntimeException('serp_scrape_failed:google:'.$response->status());
        }

        return static::extractGoogleLinks($response->body());
    }

    /**
     * 抓取用 HTTP 客户端：桌面浏览器 UA + 按关键词语言本地化 Accept-Language，
     * 降低被搜索引擎反爬识别的概率（与 AuditEngine 爬虫 UA 区分开）
     */
    protected function scrapeHttp(?string $locale): PendingRequest
    {
        return Http::timeout(12)
            ->withOptions(['verify' => false])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => static::localeTag($locale).',en;q=0.5',
            ]);
    }

    /**
     * Bing b_algo 结果块 → 真实链接序列（跳转壳解包 + 站内搜索链接丢弃）
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function extractBingLinks(string $html): array
    {
        preg_match_all('#<li class="b_algo"[^>]*>.*?<a[^>]+href="([^"]+)"#is', $html, $matches);

        $links = [];

        foreach (array_unique($matches[1]) as $url) {
            $real = static::unwrapBingRedirect(html_entity_decode(Typed::string($url), ENT_QUOTES, 'UTF-8'));

            if ($real !== null) {
                $links[] = ['link' => $real];
            }
        }

        return array_slice($links, 0, 50);
    }

    /**
     * Bing 跳转链接解包：现行 /ck/a?...u=a1<base64url> 与旧版 ?uddg=<urlencode>；
     * 非 bing 域直接放行（/search 站内链接丢弃）
     */
    protected static function unwrapBingRedirect(string $url): ?string
    {
        if (! str_starts_with($url, 'http')) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($host === '') {
            return null;
        }

        if (! str_ends_with($host, 'bing.com')) {
            return str_contains($path, '/search') ? null : $url;
        }

        if (! str_contains($path, '/ck/')) {
            return null;
        }

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $u = Typed::string($query['u'] ?? $query['uddg'] ?? '');

        if ($u === '') {
            return null;
        }

        // u=a1 前缀 + base64url(真实地址) 为现行格式；否则按 urlencode 直链处理
        $candidate = str_starts_with($u, 'a1')
            ? static::base64UrlDecode(substr($u, 2))
            : urldecode($u);

        return ($candidate !== null && str_starts_with($candidate, 'http')) ? $candidate : null;
    }

    /**
     * base64url 解码（补齐 padding；结果非 http(s) 视为解包失败）
     */
    protected static function base64UrlDecode(string $value): ?string
    {
        $raw = strtr(rtrim($value, '='), '-_', '+/');
        $pad = strlen($raw) % 4;

        if ($pad !== 0) {
            $raw .= str_repeat('=', 4 - $pad);
        }

        $decoded = base64_decode($raw, true);

        return (is_string($decoded) && str_starts_with($decoded, 'http')) ? $decoded : null;
    }

    /**
     * 百度 mu= 真实落地 URL 序列（按出现顺序去重；/link 跳转壳除外）
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function extractBaiduLinks(string $html): array
    {
        preg_match_all('/mu="([^"]+)"/', $html, $matches);

        $links = [];

        foreach (array_unique($matches[1]) as $url) {
            $url = html_entity_decode(Typed::string($url), ENT_QUOTES, 'UTF-8');

            if (str_starts_with($url, 'http') && ! str_contains($url, 'baidu.com/link')) {
                $links[] = ['link' => $url];
            }
        }

        return array_slice($links, 0, 50);
    }

    /**
     * Google 简版结果页链接提取：/url?q=（curl UA 场景）与直接外链合并，
     * 过滤 Google 自有域后按出现顺序去重
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function extractGoogleLinks(string $html): array
    {
        preg_match_all('#href="/url\?q=(https?[^&"]+)#i', $html, $via);
        preg_match_all('#<a[^>]+href="(https?://[^"]+)"#i', $html, $direct);

        $links = [];

        foreach (array_merge($via[1], $direct[1]) as $candidate) {
            $url = html_entity_decode(Typed::string($candidate), ENT_QUOTES, 'UTF-8');
            $host = strtolower((string) parse_url($url, PHP_URL_HOST));

            if ($host === '' || str_contains($host, 'google.') || str_contains($host, 'gstatic.') || str_contains($host, 'googleusercontent.')) {
                continue;
            }

            $links[$url] = ['link' => $url];
        }

        return array_slice(array_values($links), 0, 50);
    }

    /** locale 归一化为 Accept-Language / hl / setlang 使用的 xx-YY 形式 */
    protected static function localeTag(?string $locale): string
    {
        $tag = str_replace('_', '-', trim(Typed::string($locale)));

        return Typed::nonEmpty($tag, 'zh-CN');
    }

    protected static function hostOfTarget(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        $host = (string) parse_url($url, PHP_URL_HOST);

        return strtolower(preg_replace('/^www\./i', '', $host) ?? $host);
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

        return strtoupper(Typed::nonEmpty(end($parts), 'CN'));
    }
}
