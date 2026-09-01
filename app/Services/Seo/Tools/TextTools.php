<?php

namespace App\Services\Seo\Tools;

/**
 * 文本与内容工具组
 */
class TextTools
{
    public function wordCounter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        // charlist 中的 "-" 置于末尾避免被解析为范围；勿写 ".."（空范围会抛 ValueError）
        $latin = str_word_count($text, 0, '0123456789-');
        $cjk = (int) preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);

        return ['ok' => true, 'data' => [
            '总词数' => $latin + $cjk,
            '西文词数' => $latin,
            '中文字符' => $cjk,
            '字符数（不含空格）' => mb_strlen(str_replace(' ', '', $text)),
            '行数' => count(explode("\n", $text)),
        ]];
    }

    public function charCounter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        return ['ok' => true, 'data' => [
            '字符数' => mb_strlen($text),
            '不含空格' => mb_strlen(str_replace(' ', '', $text)),
            '句子数' => (int) preg_match_all('/[.!?。！？]+/u', $text),
            '段落数' => count(array_filter(explode("\n", trim($text)))),
        ]];
    }

    public function caseConverter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $mode = (string) ($in['mode'] ?? 'upper');

        $converted = match ($mode) {
            'upper' => mb_strtoupper($text),
            'lower' => mb_strtolower($text),
            'title' => mb_convert_case($text, MB_CASE_TITLE, 'UTF-8'),
            'sentence' => ucfirst(mb_strtolower($text)),
            'camel' => lcfirst(str_replace(' ', '', ucwords(preg_replace('/[_-]+/', ' ', strtolower($text))))),
            'snake' => strtolower((string) preg_replace('/\s+/', '_', trim($text))),
            'kebab' => strtolower((string) preg_replace('/\s+/', '-', trim($text))),
            default => $text,
        };

        return ['ok' => true, 'data' => [], 'text' => (string) $converted];
    }

    public function slugConverter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $separator = ($in['separator'] ?? '-') === '_' ? '_' : '-';

        $slug = mb_strtolower(trim($text));
        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', $separator, $slug);
        $slug = trim($slug, $separator);

        return ['ok' => true, 'data' => ['slug' => $slug]];
    }

    public function textReplacer(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $search = (string) ($in['search'] ?? '');
        $replace = (string) ($in['replace'] ?? '');

        if ($search === '') {
            return ['ok' => false, 'error' => '请输入查找内容', 'data' => []];
        }

        $count = substr_count($text, $search);

        return ['ok' => true, 'data' => ['替换次数' => $count], 'text' => str_replace($search, $replace, $text)];
    }

    public function textReverser(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        return ['ok' => true, 'data' => [], 'text' => implode('', array_reverse(mb_str_split($text)))];
    }

    public function loremGenerator(array $in): array
    {
        $paragraphs = min(10, max(1, (int) ($in['paragraphs'] ?? 3)));

        $words = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud', 'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo', 'consequat'];

        $out = [];
        for ($p = 0; $p < $paragraphs; $p++) {
            $lines = [];

            for ($s = 0, $sentences = random_int(3, 6); $s < $sentences; $s++) {
                $sentence = [];

                for ($w = 0, $count = random_int(8, 16); $w < $count; $w++) {
                    $sentence[] = $words[array_rand($words)];
                }

                $lines[] = ucfirst(implode(' ', $sentence)).'.';
            }

            $out[] = implode(' ', $lines);
        }

        return ['ok' => true, 'data' => [], 'text' => implode("\n\n", $out)];
    }

    public function readingTime(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $wpm = max(50, min(1000, (int) ($in['wpm'] ?? 225)));

        $latin = str_word_count($text, 0, '0123456789..-');
        $cjk = (int) preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text);
        $total = max(1, $latin + $cjk);

        $minutes = $total / $wpm;

        return ['ok' => true, 'data' => [
            '阅读速度' => $wpm.' 词/分钟',
            '总词数' => $total,
            '阅读时长' => $minutes < 1 ? ceil($minutes * 60).' 秒' : ceil($minutes).' 分钟',
        ]];
    }

    public function timestampConverter(array $in): array
    {
        $value = trim((string) ($in['value'] ?? ''));

        if ($value === '') {
            return ['ok' => false, 'error' => '请输入时间戳或日期', 'data' => []];
        }

        if (ctype_digit($value)) {
            // 10 位按秒、13 位按毫秒
            $timestamp = strlen($value) === 13 ? (int) substr($value, 0, 10) : (int) $value;

            return ['ok' => true, 'data' => [
                '时间戳' => $value,
                '日期时间' => date('Y-m-d H:i:s', $timestamp),
                'ISO 8601' => date('c', $timestamp),
            ]];
        }

        $parsed = strtotime($value);

        if ($parsed === false) {
            return ['ok' => false, 'error' => '无法识别的日期格式', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '时间戳' => $parsed,
            '日期时间' => date('Y-m-d H:i:s', $parsed),
        ]];
    }

    public function keywordDensityText(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $keyword = mb_strtolower(trim((string) ($in['keyword'] ?? '')));

        if (trim($text) === '' || $keyword === '') {
            return ['ok' => false, 'error' => '请输入文本与关键词', 'data' => []];
        }

        $total = max(1, str_word_count($text, 0, '0123456789..-') + (int) preg_match_all('/[\x{4e00}-\x{9fff}]/u', $text));

        $count = substr_count(mb_strtolower($text), $keyword);

        return ['ok' => true, 'data' => [
            '出现次数' => $count,
            '总词数' => $total,
            '密度' => round($count / $total * 100, 2).'%',
            '建议' => '理想密度为 1%-3%',
        ]];
    }
}
