<?php

namespace App\Support;

/**
 * 大洲代码 → 可读展示名（统计面板展示层辅助）
 *
 * 数据库存储 ISO 大洲代码（AS/EU/NA/SA/AF/OC），
 * 展示层需转换为人类可读名称。
 * 关联：GeoIp（采集侧写入 continent_code）、StatsController topContinents
 */
class ContinentNames
{
    /** @var array<string, string> ISO code → 中文名 */
    protected const ZH = [
        'AS' => '亚洲', 'EU' => '欧洲', 'NA' => '北美洲',
        'SA' => '南美洲', 'AF' => '非洲', 'OC' => '大洋洲',
        'AN' => '南极洲',
    ];

    /** @var array<string, string> ISO code → English name */
    protected const EN = [
        'AS' => 'Asia', 'EU' => 'Europe', 'NA' => 'North America',
        'SA' => 'South America', 'AF' => 'Africa', 'OC' => 'Oceania',
        'AN' => 'Antarctica',
    ];

    public static function name(?string $code, ?string $locale = null): string
    {
        $code = strtoupper(trim((string) $code));

        if ($code === '') {
            return '';
        }

        $table = in_array($locale, ['zh_CN', 'zh_TW'], true) ? self::ZH : self::EN;

        return $table[$code] ?? $code;
    }
}
