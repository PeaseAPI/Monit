<?php

namespace App\Services\Seo\Tools;

use App\Services\Seo\AuditEngine;
use App\Services\Seo\SitemapMonitor;
use App\Services\Seo\Tests\ContentTests;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * SEO 检查工具组
 */
class SeoCheckTools
{
    /**
     * 抓取并解析页面（各检查工具共享）
     *
     * @return array{ok:bool, html:string, headers:array, dom:?\DOMDocument, status:int, error?:string}
     */
    protected function fetchPage(array $in): array
    {
        $url = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));

        try {
            $response = Http::timeout(20)->withOptions(['verify' => false])->get($url);
        } catch (Throwable $e) {
            return ['ok' => false, 'html' => '', 'headers' => [], 'dom' => null, 'status' => 0, 'error' => mb_substr($e->getMessage(), 0, 200)];
        }

        $html = (string) $response->body();

        $dom = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return ['ok' => true, 'html' => $html, 'headers' => $response->headers(), 'dom' => $dom, 'status' => $response->status()];
    }

    protected function metaOf(\DOMDocument $dom, string $name): ?string
    {
        foreach (['name', 'property', 'http-equiv'] as $attr) {
            foreach ($dom->getElementsByTagName('meta') as $node) {
                if (strcasecmp((string) $node->getAttribute($attr), $name) === 0) {
                    return $node->getAttribute('content') ?: null;
                }
            }
        }

        return null;
    }

    public function metaTags(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'title' => (string) $page['dom']->getElementsByTagName('title')->item(0)?->textContent,
            'description' => (string) ($this->metaOf($page['dom'], 'description') ?? ''),
            'keywords' => (string) ($this->metaOf($page['dom'], 'keywords') ?? ''),
            'viewport' => (string) ($this->metaOf($page['dom'], 'viewport') ?? ''),
            'charset' => (string) ($this->metaOf($page['dom'], 'charset') ?? ''),
            'robots' => (string) ($this->metaOf($page['dom'], 'robots') ?? ''),
            'og:title' => (string) ($this->metaOf($page['dom'], 'og:title') ?? ''),
            'og:description' => (string) ($this->metaOf($page['dom'], 'og:description') ?? ''),
            'og:image' => (string) ($this->metaOf($page['dom'], 'og:image') ?? ''),
        ]];
    }

    public function keywordDensity(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($page['html']), ENT_QUOTES, 'UTF-8')) ?? '');

        return $this->density($text, (int) ($in['min_length'] ?? 3));
    }

    /**
     * 关键词密度计算核心
     */
    protected function density(string $text, int $minLength = 3): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [];
        $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) >= $minLength));

        if ($words === []) {
            return ['ok' => false, 'error' => '无可分析文本', 'data' => []];
        }

        $total = count($words);
        $counts = array_count_values($words);
        arsort($counts);

        $data = ['总词数' => $total];

        foreach (array_slice($counts, 0, 15, true) as $word => $count) {
            $data["{$word}（{$count} 次）"] = round($count / $total * 100, 2).'%';
        }

        return ['ok' => true, 'data' => $data];
    }

    public function openGraph(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $data = [];
        $count = 0;
        foreach ($page['dom']->getElementsByTagName('meta') as $meta) {
            $property = (string) $meta->getAttribute('property');

            if (str_starts_with($property, 'og:')) {
                $data[$property] = mb_substr((string) $meta->getAttribute('content'), 0, 200);
                $count++;
            }
        }

        return ['ok' => true, 'data' => ['标签数量' => $count, ...$data]];
    }

    public function twitterCard(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $data = [];
        foreach ($page['dom']->getElementsByTagName('meta') as $meta) {
            $name = (string) $meta->getAttribute('name');

            if (str_starts_with($name, 'twitter:')) {
                $data[$name] = mb_substr((string) $meta->getAttribute('content'), 0, 200);
            }
        }

        return ['ok' => true, 'data' => $data !== [] ? $data : ['结果' => '未发现 Twitter Card 标签']];
    }

    public function robotsTxt(array $in): array
    {
        $url = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));
        $host = (string) parse_url($url, PHP_URL_HOST);
        $scheme = (string) (parse_url($url, PHP_URL_SCHEME) ?: 'https');

        try {
            $response = Http::timeout(15)->get("{$scheme}://{$host}/robots.txt");
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        if (! $response->successful()) {
            return ['ok' => true, 'data' => ['状态' => 'robots.txt 不存在（HTTP '.$response->status().'）']];
        }

        $body = (string) $response->body();
        preg_match_all('/Sitemap:\s*(\S+)/i', $body, $sitemaps);

        return ['ok' => true, 'data' => [
            '大小' => strlen($body).' 字节',
            '声明 Sitemap' => implode(', ', $sitemaps[1] ?? []) ?: '未声明',
        ], 'text' => mb_substr($body, 0, 3000)];
    }

    public function sitemapChecker(array $in): array
    {
        $url = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));
        $host = (string) parse_url($url, PHP_URL_HOST);
        $scheme = (string) (parse_url($url, PHP_URL_SCHEME) ?: 'https');

        $result = app(SitemapMonitor::class)->fetch("{$scheme}://{$host}/sitemap.xml");

        if (! $result['ok']) {
            return ['ok' => false, 'error' => $result['error'] ?? 'sitemap.xml 不可用', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'URL 总数' => count($result['urls']),
        ], 'text' => implode("\n", array_slice($result['urls'], 0, 200))];
    }

    public function mixedContent(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        preg_match_all('/(?:src|href)=["\']http:\/\/[^"\']+["\']/i', $page['html'], $matches);

        $urls = array_slice($matches[0] ?? [], 0, 50);

        return ['ok' => true, 'data' => [
            'HTTP 不安全资源' => count($matches[0] ?? []),
        ], 'text' => $urls ? implode("\n", $urls) : null];
    }

    public function safeUrl(array $in): array
    {
        $url = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));
        $parts = parse_url($url);

        $issues = [];

        if (($parts['scheme'] ?? '') !== 'https') {
            $issues[] = '未使用 HTTPS';
        }

        $path = (string) ($parts['path'] ?? '');

        if (preg_match('/[A-Z]/', $path)) {
            $issues[] = '路径包含大写字母';
        }

        if (mb_strlen($url) > 115) {
            $issues[] = 'URL 超过 115 字符';
        }

        return ['ok' => true, 'data' => [
            '结论' => $issues === [] ? 'URL 结构对 SEO 友好' : '存在以下问题',
            '问题' => $issues === [] ? '-' : implode('；', $issues),
        ]];
    }

    public function faviconChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $found = [];
        foreach ($page['dom']->getElementsByTagName('link') as $link) {
            if (str_contains(strtolower((string) $link->getAttribute('rel')), 'icon')) {
                $found[] = $link->getAttribute('rel').': '.mb_substr($link->getAttribute('href'), 0, 150);
            }
        }

        return ['ok' => true, 'data' => ['声明' => $found !== [] ? implode(' | ', $found) : '未声明 favicon']];
    }

    public function h1Checker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $h1s = [];
        foreach ($page['dom']->getElementsByTagName('h1') as $h1) {
            $h1s[] = mb_substr(trim($h1->textContent), 0, 120);
        }

        return ['ok' => true, 'data' => [
            'H1 数量' => count($h1s),
            '建议' => count($h1s) === 1 ? '符合规范（唯一 H1）' : '页面应有且仅有一个 H1',
        ], 'text' => $h1s ? implode("\n", $h1s) : null];
    }

    public function imageAlt(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $missing = [];
        foreach ($page['dom']->getElementsByTagName('img') as $img) {
            if (trim($img->getAttribute('alt')) === '') {
                $missing[] = mb_substr($img->getAttribute('src'), 0, 150);
            }
        }

        return ['ok' => true, 'data' => [
            '图片总数' => $page['dom']->getElementsByTagName('img')->length,
            '缺失 alt' => count($missing),
        ], 'text' => $missing ? implode("\n", array_slice($missing, 0, 50)) : null];
    }

    /**
     * 站内死链探测（同源链接抽样检查，上限 20）
     */
    public function brokenLinks(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $origin = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));
        $base = (string) (parse_url($origin, PHP_URL_SCHEME) ?: 'https').'://'.(string) parse_url($origin, PHP_URL_HOST);

        $links = [];
        foreach ($page['dom']->getElementsByTagName('a') as $a) {
            $href = trim($a->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            if (! preg_match('#^https?://#i', $href)) {
                $href = $base.'/'.ltrim($href, '/');
            }

            $links[$href] = true;

            if (count($links) >= 20) {
                break;
            }
        }

        $broken = [];
        foreach (array_keys($links) as $link) {
            try {
                $status = Http::timeout(10)->withOptions(['verify' => false])->head($link)->status();
            } catch (Throwable) {
                $status = 0;
            }

            if ($status >= 400 || $status === 0) {
                $broken[] = "{$status} {$link}";
            }
        }

        return ['ok' => true, 'data' => [
            '检查链接数' => count($links),
            '失效链接' => count($broken),
        ], 'text' => $broken ? implode("\n", $broken) : null];
    }

    public function urlSeo(array $in): array
    {
        $url = AuditEngine::normalizeUrl((string) ($in['url'] ?? ''));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $segments = array_filter(explode('/', $path));

        $issues = [];
        if (preg_match('/[A-Z]/', $path)) {
            $issues[] = '包含大写字母';
        }
        if (str_contains($path, '_')) {
            $issues[] = '使用下划线（建议连字符）';
        }
        if (mb_strlen($path) > 115) {
            $issues[] = '路径过长';
        }

        return ['ok' => true, 'data' => [
            '路径' => $path === '' ? '/' : mb_substr($path, 0, 150),
            '层级深度' => count($segments),
            '结论' => $issues === [] ? 'SEO 友好' : implode('；', $issues),
        ]];
    }

    public function canonicalChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $canonical = '';
        foreach ($page['dom']->getElementsByTagName('link') as $link) {
            if (strtolower((string) $link->getAttribute('rel')) === 'canonical') {
                $canonical = $link->getAttribute('href');

                break;
            }
        }

        return ['ok' => true, 'data' => ['canonical' => $canonical !== '' ? mb_substr($canonical, 0, 200) : '未声明']];
    }

    public function hreflangChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $data = [];
        foreach ($page['dom']->getElementsByTagName('link') as $link) {
            if (strtolower((string) $link->getAttribute('rel')) === 'alternate' && $link->hasAttribute('hreflang')) {
                $data[$link->getAttribute('hreflang')] = mb_substr($link->getAttribute('href'), 0, 150);
            }
        }

        return ['ok' => true, 'data' => $data !== [] ? $data : ['结果' => '未声明 hreflang']];
    }

    public function structuredData(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        preg_match_all('/<script[^>]+ld\+json[^>]*>(.*?)<\/script>/is', $page['html'], $matches);

        $valid = 0;

        foreach ($matches[1] ?? [] as $json) {
            if (json_decode(trim($json)) !== null) {
                $valid++;
            }
        }

        return ['ok' => true, 'data' => [
            '结构化数据块' => count($matches[1] ?? []),
            'JSON 有效' => $valid,
        ]];
    }

    public function viewportChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'viewport' => (string) ($this->metaOf($page['dom'], 'viewport') ?: '未声明（移动端适配缺失）'),
        ]];
    }

    public function languageChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'html lang' => (string) ($page['dom']->documentElement->getAttribute('lang') ?: '未声明'),
            'Content-Language 头' => (string) ($this->headerOf($page['headers'], 'content-language') ?? '未设置'),
        ]];
    }

    public function charsetChecker(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $charset = (string) ($this->metaOf($page['dom'], 'charset') ?? '');

        if ($charset === '') {
            $contentType = (string) ($this->headerOf($page['headers'], 'content-type') ?? '');
            $charset = str_contains($contentType, 'charset=') ? trim((string) preg_replace('/.*charset=([^\s;]+).*/i', '$1', $contentType)) : '';
        }

        return ['ok' => true, 'data' => ['字符集' => $charset !== '' ? $charset : '未声明']];
    }

    protected function headerOf(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return null;
    }

    public function textHtmlRatio(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $text = strip_tags($page['html']);
        $ratio = round(mb_strlen($text) / max(1, strlen($page['html'])) * 100, 2);

        return ['ok' => true, 'data' => [
            '文本占比' => $ratio.'%',
            '建议' => $ratio >= 10 ? '正常' : '低于 10%，HTML 冗余过多',
        ]];
    }

    public function cacheHeaders(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'Cache-Control' => (string) ($this->headerOf($page['headers'], 'cache-control') ?? '未设置'),
            'ETag' => (string) ($this->headerOf($page['headers'], 'etag') ?? '未设置'),
            'Last-Modified' => (string) ($this->headerOf($page['headers'], 'last-modified') ?? '未设置'),
            'Expires' => (string) ($this->headerOf($page['headers'], 'expires') ?? '未设置'),
        ]];
    }

    public function securityHeaders(array $in): array
    {
        $page = $this->fetchPage($in);

        if (! $page['ok']) {
            return ['ok' => false, 'error' => $page['error'], 'data' => []];
        }

        $checks = ['strict-transport-security', 'content-security-policy', 'x-content-type-options', 'x-frame-options', 'referrer-policy'];

        $data = [];
        $missing = [];

        foreach ($checks as $header) {
            $value = $this->headerOf($page['headers'], $header);

            $data[$header] = $value !== null ? mb_substr($value, 0, 100) : '未设置';

            if ($value === null) {
                $missing[] = $header;
            }
        }

        $data['缺失'] = $missing === [] ? '无' : count($missing).' 项';

        return ['ok' => true, 'data' => $data];
    }

    public function emailExtractor(array $in): array
    {
        preg_match_all('/[\w.+-]+@[\w-]+\.[\w.]+/', (string) ($in['text'] ?? ''), $matches);

        $emails = array_unique($matches[0] ?? []);

        return ['ok' => true, 'data' => ['数量' => count($emails)], 'text' => $emails ? implode("\n", $emails) : null];
    }

    public function linkExtractor(array $in): array
    {
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', (string) ($in['text'] ?? ''), $matches);

        $links = array_unique($matches[1] ?? []);

        return ['ok' => true, 'data' => ['数量' => count($links)], 'text' => $links ? implode("\n", $links) : null];
    }

    public function imageExtractor(array $in): array
    {
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', (string) ($in['text'] ?? ''), $matches);

        $images = array_unique($matches[1] ?? []);

        return ['ok' => true, 'data' => ['数量' => count($images)], 'text' => $images ? implode("\n", $images) : null];
    }

    public function headingExtractor(array $in): array
    {
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', (string) ($in['text'] ?? ''), $matches, PREG_SET_ORDER);

        $lines = [];
        foreach ($matches as $m) {
            $lines[] = str_repeat('#', (int) $m[1]).' '.trim(strip_tags($m[2]));
        }

        return ['ok' => true, 'data' => ['数量' => count($lines)], 'text' => $lines ? implode("\n", $lines) : null];
    }

    public function keywordExtractor(array $in): array
    {
        $top = ContentTests::topKeywords((string) ($in['text'] ?? ''), 20);

        return ['ok' => true, 'data' => ['关键词' => $top ? implode('、', $top) : '无']];
    }

    public function uptimeCalculator(array $in): array
    {
        $downtime = max(0, (int) ($in['downtime_minutes'] ?? 0));
        $days = max(1, (int) ($in['period_days'] ?? 30));

        $totalMinutes = $days * 1440;
        $uptime = round(($totalMinutes - $downtime) / $totalMinutes * 100, 4);

        return ['ok' => true, 'data' => [
            '可用率' => $uptime.'%',
            'SLA 99.9% 允许停机' => round($totalMinutes * 0.001, 1).' 分钟',
            'SLA 99.99% 允许停机' => round($totalMinutes * 0.0001, 1).' 分钟',
        ]];
    }

    /**
     * 可读性（Flesch 阅读易读度 + 中文平均句长）
     */
    public function readability(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        if (trim($text) === '') {
            return ['ok' => false, 'error' => '请输入文本', 'data' => []];
        }

        $sentences = max(1, (int) preg_match_all('/[.!?。！？]+/u', $text));
        $words = preg_split('/\s+/u', trim((string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text)) ?? '', -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = max(1, count($words));
        $cjk = (int) preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);

        $syllables = 0;
        foreach ($words as $w) {
            $syllables += max(1, (int) preg_match_all('/[aeiouy]+/i', $w));
        }

        $flesch = 206.835 - 1.015 * ($wordCount / $sentences) - 84.6 * ($syllables / $wordCount);
        $avgSentenceLength = ($cjk + $wordCount) / $sentences;

        return ['ok' => true, 'data' => [
            'Flesch 易读度' => round(max(0, min(100, $flesch)), 1),
            '平均句长' => round($avgSentenceLength, 1).' 词/句',
            '中文评级' => $avgSentenceLength <= 20 ? '易读' : ($avgSentenceLength <= 30 ? '适中' : '偏难'),
        ]];
    }

    public function metaLength(array $in): array
    {
        $title = (string) ($in['title'] ?? '');
        $description = (string) ($in['description'] ?? '');

        return ['ok' => true, 'data' => [
            '标题长度' => mb_strlen($title).' 字符（建议 10-60）',
            '描述长度' => mb_strlen($description).' 字符（建议 50-160）',
            '标题结论' => (mb_strlen($title) >= 10 && mb_strlen($title) <= 60) ? '合格' : '需调整',
            '描述结论' => (mb_strlen($description) >= 50 && mb_strlen($description) <= 160) ? '合格' : '需调整',
        ]];
    }

    /**
     * 快速体检：复用审计引擎
     */
    public function seoScore(array $in): array
    {
        $audit = app(AuditEngine::class)->run((string) ($in['url'] ?? ''));

        if ($audit->status !== 'completed') {
            return ['ok' => false, 'error' => '页面抓取失败：'.($audit->error ?: '未知'), 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '总分' => $audit->score.'/100',
            '通过测试' => $audit->passed_tests.' / '.$audit->total_tests,
            '重大问题' => $audit->major_issues,
            '中等问题' => $audit->moderate_issues,
            '轻微问题' => $audit->minor_issues,
        ]];
    }

    /**
     * 两页相似度（3-gram shingle，重复内容检测）
     */
    public function duplicateContent(array $in): array
    {
        try {
            $a = (string) Http::timeout(20)->get(AuditEngine::normalizeUrl((string) ($in['url_a'] ?? '')))->body();
            $b = (string) Http::timeout(20)->get(AuditEngine::normalizeUrl((string) ($in['url_b'] ?? '')))->body();
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        $shingles = static function (string $html): array {
            $words = array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower(strip_tags($html))) ?: []));
            $set = [];
            for ($i = 0; $i + 3 <= count($words); $i++) {
                $set[implode(' ', array_slice($words, $i, 3))] = true;
            }

            return $set;
        };

        $setA = $shingles($a);
        $setB = $shingles($b);

        if ($setA === [] || $setB === []) {
            return ['ok' => false, 'error' => '页面内容为空', 'data' => []];
        }

        $common = count(array_intersect_key($setA, $setB));
        $similarity = round($common / max(count($setA), count($setB)) * 100, 1);

        return ['ok' => true, 'data' => [
            '相似度' => $similarity.'%',
            '结论' => $similarity > 80 ? '高度重复，建议重写' : ($similarity > 50 ? '部分重复，需关注' : '原创度良好'),
        ]];
    }

    /**
     * 邮箱防爬混淆
     */
    public function emailProtector(array $in): array
    {
        $email = trim((string) ($in['email'] ?? ''));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => '邮箱格式无效', 'data' => []];
        }

        [$user, $domain] = explode('@', $email);

        return ['ok' => true, 'data' => [
            'HTML 实体方案' => '<a href="mailto:'.e($user).'&#64;'.e($domain).'">'.e($user).'&#64;'.e($domain).'</a>',
            'JS 拼接方案' => '<script>document.write("'.addslashes($user).'"+String.fromCharCode(64)+"'.addslashes($domain).'");</script>',
        ]];
    }

    /**
     * Ahrefs DR（条件工具：后台配置 API Key 后开放）
     */
    public function ahrefsDomainRating(array $in): array
    {
        $apiKey = (string) Settings::get('seo.ahrefs_api_key', '');

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Ahrefs API Key 尚未配置', 'data' => []];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(20)
                ->get('https://api.ahrefs.com/v3/site-explorer/domain-rating', [
                    'target' => preg_replace('#^https?://#', '', trim((string) ($in['domain'] ?? ''))),
                    'date' => now()->toDateString(),
                ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        if (! $response->successful()) {
            return ['ok' => false, 'error' => 'Ahrefs 接口返回 HTTP '.$response->status(), 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'Domain Rating' => (string) ($response->json('domain_rating') ?? '-'),
        ]];
    }
}
