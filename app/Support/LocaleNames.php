<?php

namespace App\Support;

/**
 * 浏览器语言代码 → 可读展示名（统计面板展示层辅助）
 *
 * 浏览器 navigator.language 返回的 locale 代码（如 zh-CN、en-US）
 * 直接展示在统计面板可读性差，本类提供 code → 人类可读名称的映射。
 * 关联：StatsController topLanguages / rank-panel 视图
 */
class LocaleNames
{
    /** @var array<string, string> locale code → 中文名 */
    protected const ZH = [
        'zh-CN' => '中文（简体）', 'zh-TW' => '中文（繁體）', 'zh-HK' => '中文（香港）', 'zh' => '中文',
        'zh-MO' => '中文（澳門）', 'zh-SG' => '中文（新加坡）',
        'en-US' => '英语（美国）', 'en-GB' => '英语（英国）', 'en-AU' => '英语（澳大利亚）',
        'en-CA' => '英语（加拿大）', 'en-IN' => '英语（印度）', 'en' => '英语',
        'ja' => '日语', 'ko' => '韩语', 'fr' => '法语', 'de' => '德语',
        'es' => '西班牙语', 'pt-BR' => '葡萄牙语（巴西）', 'pt-PT' => '葡萄牙语（葡萄牙）',
        'pt' => '葡萄牙语', 'it' => '意大利语', 'ru' => '俄语', 'ar' => '阿拉伯语',
        'hi' => '印地语', 'th' => '泰语', 'vi' => '越南语', 'id' => '印尼语',
        'ms' => '马来语', 'tr' => '土耳其语', 'nl' => '荷兰语', 'pl' => '波兰语',
        'sv' => '瑞典语', 'no' => '挪威语', 'da' => '丹麦语', 'fi' => '芬兰语',
        'el' => '希腊语', 'cs' => '捷克语', 'ro' => '罗马尼亚语', 'hu' => '匈牙利语',
        'uk' => '乌克兰语', 'he' => '希伯来语', 'fa' => '波斯语', 'bn' => '孟加拉语',
        'ta' => '泰米尔语', 'ur' => '乌尔都语', 'sw' => '斯瓦希里语',
    ];

    /** @var array<string, string> locale code → English name */
    protected const EN = [
        'zh-CN' => 'Chinese (Simplified)', 'zh-TW' => 'Chinese (Traditional)', 'zh-HK' => 'Chinese (Hong Kong)', 'zh' => 'Chinese',
        'zh-MO' => 'Chinese (Macau)', 'zh-SG' => 'Chinese (Singapore)',
        'en-US' => 'English (US)', 'en-GB' => 'English (UK)', 'en-AU' => 'English (Australia)',
        'en-CA' => 'English (Canada)', 'en-IN' => 'English (India)', 'en' => 'English',
        'ja' => 'Japanese', 'ko' => 'Korean', 'fr' => 'French', 'de' => 'German',
        'es' => 'Spanish', 'pt-BR' => 'Portuguese (Brazil)', 'pt-PT' => 'Portuguese (Portugal)',
        'pt' => 'Portuguese', 'it' => 'Italian', 'ru' => 'Russian', 'ar' => 'Arabic',
        'hi' => 'Hindi', 'th' => 'Thai', 'vi' => 'Vietnamese', 'id' => 'Indonesian',
        'ms' => 'Malay', 'tr' => 'Turkish', 'nl' => 'Dutch', 'pl' => 'Polish',
        'sv' => 'Swedish', 'no' => 'Norwegian', 'da' => 'Danish', 'fi' => 'Finnish',
        'el' => 'Greek', 'cs' => 'Czech', 'ro' => 'Romanian', 'hu' => 'Hungarian',
        'uk' => 'Ukrainian', 'he' => 'Hebrew', 'fa' => 'Persian', 'bn' => 'Bengali',
        'ta' => 'Tamil', 'ur' => 'Urdu', 'sw' => 'Swahili',
    ];

    /**
     * 语言代码 → 可读名称
     * - 精确匹配（zh-CN → 中文（简体））
     * - 回退：取主语言码（zh-CN → zh → 中文）
     * - 最终回退：原样返回代码
     */
    public static function name(?string $code, ?string $locale = null): string
    {
        $code = trim((string) $code);

        if ($code === '') {
            return '';
        }

        $table = in_array($locale, ['zh_CN', 'zh_TW'], true) ? self::ZH : self::EN;

        // 精确匹配
        if (isset($table[$code])) {
            return $table[$code];
        }

        // 回退到主语言码（zh-CN → zh）
        $parts = explode('-', $code);
        if (count($parts) > 1 && isset($table[$parts[0]])) {
            return $table[$parts[0]];
        }

        // 尝试不区分大小写
        $lower = strtolower($code);
        foreach ($table as $k => $v) {
            if (strtolower($k) === $lower) {
                return $v;
            }
        }

        return $code;
    }
}
