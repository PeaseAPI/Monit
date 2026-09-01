<?php

namespace App\Services\Seo;

use App\Models\SeoBacklink;
use Illuminate\Support\Facades\Http;

/**
 * 反链活性验证服务（无外部 API 依赖）
 *
 * 抓取 source_url 页面 HTML，检测是否真实包含指向目标站的 <a href>：
 * - 命中 → status=active（并可回填 anchor_text / rel）
 * - 页面可访问但未命中 → status=lost
 * - 请求失败（超时/5xx）→ 保持 pending，等待下轮重验
 */
class BacklinkChecker
{
    /**
     * 验证单条反链，返回结果状态
     */
    public function verify(SeoBacklink $backlink): string
    {
        $targetHost = $backlink->target_url
            ? SeoBacklink::normalizeHost($backlink->target_url)
            : strtolower((string) $backlink->website?->host);

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; MonitBacklinkChecker/1.0)'])
                ->get($backlink->source_url);
        } catch (\Throwable $e) {
            $backlink->update(['last_checked_at' => now()]);

            return $backlink->status; // 网络失败：保持原状态（pending 等下轮）
        }

        if ($response->serverError()) {
            $backlink->update(['last_checked_at' => now()]);

            return $backlink->status;
        }

        $html = (string) $response->body();

        $match = $targetHost !== '' ? $this->findLinkTo($html, $targetHost) : null;

        if ($match === null) {
            // 页面可访问但未找到目标链接（含 4xx 源页失效）→ 丢失
            $backlink->update([
                'status' => 'lost',
                'last_checked_at' => now(),
            ]);

            return 'lost';
        }

        $backlink->update([
            'status' => 'active',
            'anchor_text' => $backlink->anchor_text ?: mb_substr(trim($match['anchor']), 0, 512),
            'rel' => $match['rel'],
            'last_checked_at' => now(),
        ]);

        return 'active';
    }

    /**
     * 在 HTML 中查找指向目标 host 的首个 <a> 标签
     *
     * @return array{anchor: string, rel: string}|null
     */
    protected function findLinkTo(string $html, string $targetHost): ?array
    {
        if (trim($html) === '' || ! preg_match_all('/<a\b[^>]*href\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
            return null;
        }

        foreach ($matches as $m) {
            $href = html_entity_decode($m[2], ENT_QUOTES);

            $hrefHost = SeoBacklink::normalizeHost($href);

            if ($hrefHost === $targetHost || str_ends_with($hrefHost, '.'.$targetHost)) {
                $relRaw = '';

                if (preg_match('/rel\s*=\s*(["\'])(.*?)\1/i', $m[0], $relMatch)) {
                    $relRaw = strtolower($relMatch[2]);
                }

                return [
                    'anchor' => trim(preg_replace('/\s+/u', ' ', strip_tags($m[3]))),
                    'rel' => str_contains($relRaw, 'nofollow') ? 'nofollow' : 'dofollow',
                ];
            }
        }

        return null;
    }
}
