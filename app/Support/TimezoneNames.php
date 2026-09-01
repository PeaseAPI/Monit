<?php

namespace App\Support;

/**
 * 时区标识符 → 可读展示名（统计面板展示层辅助）
 *
 * 浏览器 Intl.DateTimeFormat().resolvedOptions().timeZone 返回的
 * IANA 时区标识（如 Asia/Shanghai）直接展示过长，本类提供格式化。
 * 关联：StatsController topTimezones / rank-panel 视图
 */
class TimezoneNames
{
    /**
     * 时区标识 → 可读名称
     * - Asia/Shanghai → 亚洲/上海
     * - 未知 locale 时返回原样
     * - 空值返回空串
     */
    public static function name(?string $tz, ?string $locale = null): string
    {
        $tz = trim((string) $tz);

        if ($tz === '') {
            return '';
        }

        $isZh = in_array($locale, ['zh_CN', 'zh_TW'], true);

        // 中文界面：将 IANA 大洲前缀替换为中文
        if ($isZh) {
            $replacements = [
                'Africa/' => '非洲/',
                'America/' => '美洲/',
                'Antarctica/' => '南极洲/',
                'Arctic/' => '北极/',
                'Asia/' => '亚洲/',
                'Atlantic/' => '大西洋/',
                'Australia/' => '大洋洲/',
                'Europe/' => '欧洲/',
                'Indian/' => '印度洋/',
                'Pacific/' => '太平洋/',
                'US/' => '美国/',
                'Etc/' => '',
            ];

            foreach ($replacements as $prefix => $replacement) {
                if (str_starts_with($tz, $prefix)) {
                    return $replacement . substr($tz, strlen($prefix));
                }
            }
        }

        // 非中文界面：直接返回 IANA 标识（本身即为英文可读）
        return $tz;
    }
}
