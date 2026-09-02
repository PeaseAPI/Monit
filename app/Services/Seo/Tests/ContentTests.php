<?php

namespace App\Services\Seo\Tests;

use App\Services\Seo\AuditContext;
use App\Services\Seo\AuditTestRegistry;

/**
 * 内容质量测试组（content 类别）
 */
class ContentTests
{
    public function handles(): array
    {
        return [
            'words_count' => 'wordsCount',
            'words_used' => 'wordsUsed',
            'text_to_html_ratio' => 'textToHtmlRatio',
            'social_links' => 'socialLinks',
            'emails' => 'emails',
            'content_keywords' => 'contentKeywords',
            'image_keywords' => 'imageKeywords',
        ];
    }

    public function wordsCount(AuditContext $c): array
    {
        $text = $c->bodyText();
        // 西文按空格分词，中文按字符计数
        $latin = str_word_count($text, 0, '0123456789..-');
        $cjk = preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text) ?: 0;
        $total = $latin + (int) $cjk;
        $min = (int) AuditTestRegistry::threshold('words_count_min', 300);

        return [
            'passed' => $total >= $min,
            'value' => (string) $total,
        ];
    }

    public function wordsUsed(AuditContext $c): array
    {
        $words = preg_split('/\s+/u', mb_strtolower($c->bodyText())) ?: [];
        $words = array_values(array_filter($words, fn ($w) => mb_strlen($w) > 1));

        if ($words === []) {
            return ['passed' => false, 'value' => '0'];
        }

        $unique = count(array_unique($words));
        $variety = (int) round($unique / count($words) * 100);

        // 词汇丰富度 > 30% 视为健康
        return [
            'passed' => $variety > 30,
            'value' => $unique.' / '.count($words),
        ];
    }

    public function textToHtmlRatio(AuditContext $c): array
    {
        $ratio = $c->textRatio();
        $min = (float) AuditTestRegistry::threshold('text_html_ratio_min', 10);

        return [
            'passed' => $ratio >= $min,
            'value' => $ratio.'%',
        ];
    }

    public function socialLinks(AuditContext $c): array
    {
        preg_match_all('/https?:\/\/(?:[^"\']*?)?(facebook|twitter|x\.com|instagram|linkedin|youtube|github|weibo|weixin|qq|tiktok)\./i', $c->html, $matches);

        $count = count(array_unique($matches[1] ?? []));

        return [
            'passed' => $count > 0,
            'value' => (string) $count,
        ];
    }

    public function emails(AuditContext $c): array
    {
        preg_match_all('/[\w.+-]+@[\w-]+\.[\w.]+/', $c->bodyText(), $matches);

        $count = count(array_unique($matches[0] ?? []));

        return [
            'passed' => $count > 0,
            'value' => (string) $count,
        ];
    }

    public function contentKeywords(AuditContext $c): array
    {
        $top = static::topKeywords($c->bodyText(), 5);

        return [
            'passed' => $top !== [],
            'value' => $top !== [] ? implode(', ', array_slice($top, 0, 3)) : '-',
        ];
    }

    public function imageKeywords(AuditContext $c): array
    {
        $alts = [];
        foreach ($c->dom()->getElementsByTagName('img') as $img) {
            $alt = trim($img->getAttribute('alt'));
            if ($alt !== '') {
                $alts[] = $alt;
            }
        }

        return [
            'passed' => count($alts) > 0,
            'value' => (string) count($alts),
        ];
    }

    /**
     * 高频关键词提取（去停用词）
     */
    public static function topKeywords(string $text, int $limit = 10): array
    {
        $text = mb_strtolower($text);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
        $stop = ['的', '了', '和', '是', '在', '有', '与', 'for', 'the', 'and', 'you', 'that', 'this', 'with', 'from', 'are', 'was', 'not', 'but', 'his', 'her', 'she', 'him', 'has', 'have', 'will', 'your', 'they', 'its', 'our', 'out', 'can', 'just', 'about', 'into', 'than', 'then', 'them', 'these', 'some', 'more', 'very', 'also'];

        $counts = [];
        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len < 2 || $len > 20 || in_array($word, $stop, true)) {
                continue;
            }
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, $limit);
    }
}
