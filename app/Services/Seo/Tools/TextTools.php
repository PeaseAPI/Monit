<?php

namespace App\Services\Seo\Tools;

use App\Support\Typed;

/**
 * 文本与内容工具组
 */
class TextTools
{
    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function wordCounter(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');

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

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function charCounter(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');

        return ['ok' => true, 'data' => [
            '字符数' => mb_strlen($text),
            '不含空格' => mb_strlen(str_replace(' ', '', $text)),
            '句子数' => (int) preg_match_all('/[.!?。！？]+/u', $text),
            '段落数' => count(array_filter(explode("\n", trim($text)), fn (string $v): bool => $v !== '')),
        ]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function caseConverter(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $mode = Typed::string($in['mode'] ?? 'upper');

        $converted = match ($mode) {
            'upper' => mb_strtoupper($text),
            'lower' => mb_strtolower($text),
            'title' => mb_convert_case($text, MB_CASE_TITLE, 'UTF-8'),
            'sentence' => ucfirst(mb_strtolower($text)),
            'camel' => lcfirst(str_replace(' ', '', ucwords((string) preg_replace('/[_-]+/', ' ', strtolower($text))))),
            'snake' => strtolower((string) preg_replace('/\s+/', '_', trim($text))),
            'kebab' => strtolower((string) preg_replace('/\s+/', '-', trim($text))),
            default => $text,
        };

        return ['ok' => true, 'data' => [], 'text' => $converted];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function slugConverter(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $separator = ($in['separator'] ?? '-') === '_' ? '_' : '-';

        $slug = mb_strtolower(trim($text));
        $slug = (string) preg_replace('/[^\p{L}\p{N}]+/u', $separator, $slug);
        $slug = trim($slug, $separator);

        return ['ok' => true, 'data' => ['slug' => $slug]];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function textReplacer(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $search = Typed::string($in['search'] ?? '');
        $replace = Typed::string($in['replace'] ?? '');

        if ($search === '') {
            return ['ok' => false, 'error' => '请输入查找内容', 'data' => []];
        }

        $count = substr_count($text, $search);

        return ['ok' => true, 'data' => ['替换次数' => $count], 'text' => str_replace($search, $replace, $text)];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function textReverser(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');

        return ['ok' => true, 'data' => [], 'text' => implode('', array_reverse(mb_str_split($text)))];
    }

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function loremGenerator(array $in): array
    {
        $paragraphs = min(10, max(1, Typed::int($in['paragraphs'] ?? 3)));

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

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function readingTime(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $wpm = max(50, min(1000, Typed::int($in['wpm'] ?? 225)));

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

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function timestampConverter(array $in): array
    {
        $value = trim(Typed::string($in['value'] ?? ''));

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

    /**
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function keywordDensityText(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $keyword = mb_strtolower(trim(Typed::string($in['keyword'] ?? '')));

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

    /**
     * 违禁词检测（对标 chinaz 违禁词查询：新广告法极限词 + 常见灰产词，
     * 内置基础词库 + 自定义词；返回命中词与位置，用于发布前自检）
     *
     * @param  array<string, mixed>  $in
     * @return array<string, mixed>
     */
    public function bannedWordsChecker(array $in): array
    {
        $text = Typed::string($in['text'] ?? '');
        $custom = Typed::string($in['custom'] ?? '');

        if ($text === '' && $custom === '') {
            return ['ok' => false, 'error' => '请输入待检测文本', 'data' => []];
        }

        // 基础词库：广告法极限用语 / 诱导与灰产常见词
        $banned = [
            '极限词' => ['最', '第一', '唯一', '首个', '顶级', '极品', '绝对', '终极', '最先进', '最高级',
                '最优秀', '最好', '最大', '最低价', '最便宜', '史上', '全网最', '全国第一', '世界级', '宇宙级',
                '100%有效', '彻底根治', '永不复发', '零风险', '无副作用', '立刻见效', '包治百病', '药到病除'],
            '诱导词' => ['点击领奖', '全民免单', '点击有惊喜', '秒杀全网', '错过不再', '仅此一天',
                '速来抢购', '全民疯抢', '再不抢就没'],
            '灰产词' => ['代开发票', '办理证件', '办证', '枪支', '迷药', '麻醉药', '监听器', '窃听器',
                '棋牌娱乐', '博彩', '六合彩', '时时彩', '外挂', '开挂', '破解版', '刷单', '刷钻', '刷粉',
                '网赚', '日赚千元', '套现', '洗钱', '代孕', '高仿', '复刻表', 'A货', '水货', '翻墙软件',
                '实名认证代过', '征信修复', '贷款包过', ' 秒批贷款'],
        ];

        if ($custom !== '') {
            $banned['自定义'] = array_filter(array_map('trim', preg_split('/[\r\n,，]+/', $custom) ?: []));
        }

        $hits = [];
        $totalHits = 0;

        foreach ($banned as $category => $words) {
            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }

                $count = mb_substr_count($text, $word);

                if ($count > 0) {
                    $pos = mb_strpos($text, $word);
                    $context = mb_substr($text, max(0, $pos - 10), 30);
                    $hits[] = $category.'：「'.$word.'」× '.$count.'（…'.$context.'…）';
                    $totalHits += $count;
                }
            }
        }

        $data = [
            '检测字数' => mb_strlen($text),
            '命中词数' => count($hits),
            '命中总次数' => $totalHits,
            '风险等级' => $totalHits === 0 ? '低（未命中词库）' : ($totalHits <= 3 ? '中' : '高'),
        ];

        if ($hits !== []) {
            $data['命中明细'] = '共 '.count($hits).' 类，见下方列表';
        }

        return ['ok' => true, 'data' => $data, 'text' => $hits !== [] ? implode("\n", array_slice($hits, 0, 100)) : '(未命中任何违禁词)'];
    }
}
