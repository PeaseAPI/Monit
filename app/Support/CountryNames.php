<?php

namespace App\Support;

/**
 * 国家代码 -> 展示名/国旗 emoji（统计面板展示层辅助）
 *
 * 中文界面（zh_CN / zh_TW）返回中文国名，其余语种返回英文国名；
 * 未收录的代码原样返回（ISO 两位码本身也可读）。
 * 关联：GeoIp（采集侧写入 country_code）、dashboard 统计面板
 */
class CountryNames
{
    /** @var array<string, string> ISO2 -> 中文名 */
    protected const ZH = [
        'CN' => '中国', 'HK' => '中国香港', 'MO' => '中国澳门', 'TW' => '中国台湾',
        'JP' => '日本', 'KR' => '韩国', 'KP' => '朝鲜', 'MN' => '蒙古',
        'SG' => '新加坡', 'MY' => '马来西亚', 'TH' => '泰国', 'VN' => '越南',
        'PH' => '菲律宾', 'ID' => '印度尼西亚', 'IN' => '印度', 'PK' => '巴基斯坦',
        'BD' => '孟加拉国', 'LK' => '斯里兰卡', 'NP' => '尼泊尔', 'KH' => '柬埔寨',
        'LA' => '老挝', 'MM' => '缅甸', 'KZ' => '哈萨克斯坦', 'UZ' => '乌兹别克斯坦',
        'SA' => '沙特阿拉伯', 'AE' => '阿联酋', 'IL' => '以色列', 'TR' => '土耳其',
        'IR' => '伊朗', 'IQ' => '伊拉克', 'QA' => '卡塔尔', 'KW' => '科威特',
        'US' => '美国', 'CA' => '加拿大', 'MX' => '墨西哥', 'BR' => '巴西',
        'AR' => '阿根廷', 'CL' => '智利', 'CO' => '哥伦比亚', 'PE' => '秘鲁',
        'GB' => '英国', 'IE' => '爱尔兰', 'FR' => '法国', 'DE' => '德国',
        'IT' => '意大利', 'ES' => '西班牙', 'PT' => '葡萄牙', 'NL' => '荷兰',
        'BE' => '比利时', 'LU' => '卢森堡', 'CH' => '瑞士', 'AT' => '奥地利',
        'SE' => '瑞典', 'NO' => '挪威', 'DK' => '丹麦', 'FI' => '芬兰',
        'IS' => '冰岛', 'PL' => '波兰', 'CZ' => '捷克', 'SK' => '斯洛伐克',
        'HU' => '匈牙利', 'RO' => '罗马尼亚', 'BG' => '保加利亚', 'GR' => '希腊',
        'UA' => '乌克兰', 'BY' => '白俄罗斯', 'RU' => '俄罗斯', 'LT' => '立陶宛',
        'LV' => '拉脱维亚', 'EE' => '爱沙尼亚', 'AU' => '澳大利亚', 'NZ' => '新西兰',
        'FJ' => '斐济', 'ZA' => '南非', 'EG' => '埃及', 'NG' => '尼日利亚',
        'KE' => '肯尼亚', 'GH' => '加纳', 'MA' => '摩洛哥', 'DZ' => '阿尔及利亚',
        'TN' => '突尼斯', 'ET' => '埃塞俄比亚', 'TZ' => '坦桑尼亚', 'UG' => '乌干达',
    ];

    /** @var array<string, string> ISO2 -> 英文名 */
    protected const EN = [
        'CN' => 'China', 'HK' => 'Hong Kong', 'MO' => 'Macao', 'TW' => 'Taiwan',
        'JP' => 'Japan', 'KR' => 'South Korea', 'KP' => 'North Korea', 'MN' => 'Mongolia',
        'SG' => 'Singapore', 'MY' => 'Malaysia', 'TH' => 'Thailand', 'VN' => 'Vietnam',
        'PH' => 'Philippines', 'ID' => 'Indonesia', 'IN' => 'India', 'PK' => 'Pakistan',
        'BD' => 'Bangladesh', 'LK' => 'Sri Lanka', 'NP' => 'Nepal', 'KH' => 'Cambodia',
        'LA' => 'Laos', 'MM' => 'Myanmar', 'KZ' => 'Kazakhstan', 'UZ' => 'Uzbekistan',
        'SA' => 'Saudi Arabia', 'AE' => 'United Arab Emirates', 'IL' => 'Israel', 'TR' => 'Turkey',
        'IR' => 'Iran', 'IQ' => 'Iraq', 'QA' => 'Qatar', 'KW' => 'Kuwait',
        'US' => 'United States', 'CA' => 'Canada', 'MX' => 'Mexico', 'BR' => 'Brazil',
        'AR' => 'Argentina', 'CL' => 'Chile', 'CO' => 'Colombia', 'PE' => 'Peru',
        'GB' => 'United Kingdom', 'IE' => 'Ireland', 'FR' => 'France', 'DE' => 'Germany',
        'IT' => 'Italy', 'ES' => 'Spain', 'PT' => 'Portugal', 'NL' => 'Netherlands',
        'BE' => 'Belgium', 'LU' => 'Luxembourg', 'CH' => 'Switzerland', 'AT' => 'Austria',
        'SE' => 'Sweden', 'NO' => 'Norway', 'DK' => 'Denmark', 'FI' => 'Finland',
        'IS' => 'Iceland', 'PL' => 'Poland', 'CZ' => 'Czechia', 'SK' => 'Slovakia',
        'HU' => 'Hungary', 'RO' => 'Romania', 'BG' => 'Bulgaria', 'GR' => 'Greece',
        'UA' => 'Ukraine', 'BY' => 'Belarus', 'RU' => 'Russia', 'LT' => 'Lithuania',
        'LV' => 'Latvia', 'EE' => 'Estonia', 'AU' => 'Australia', 'NZ' => 'New Zealand',
        'FJ' => 'Fiji', 'ZA' => 'South Africa', 'EG' => 'Egypt', 'NG' => 'Nigeria',
        'KE' => 'Kenya', 'GH' => 'Ghana', 'MA' => 'Morocco', 'DZ' => 'Algeria',
        'TN' => 'Tunisia', 'ET' => 'Ethiopia', 'TZ' => 'Tanzania', 'UG' => 'Uganda',
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

    /**
     * ISO2 -> 国旗 emoji（如 CN -> 🇨🇳）；空/非法输入返回空串
     */
    public static function flag(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return '';
        }

        $emoji = '';

        foreach (str_split($code) as $char) {
            $emoji .= mb_chr(0x1F1E6 + (ord($char) - 65), 'UTF-8');
        }

        return $emoji;
    }
}
