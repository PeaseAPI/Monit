<?php

namespace App\Services;

/**
 * Monit GeoIP 地理位置解析
 * MVP 阶段：无本地 mmdb 时返回空结果（预留 MaxMind GeoLite2 接入点）
 */
class GeoIp
{
    /**
     * @return array{continent_code: ?string, country_code: ?string, city_name: ?string, latitude: ?float, longitude: ?float}
     */
    public function lookup(?string $ip): array
    {
        $result = [
            'continent_code' => null,
            'country_code' => null,
            'city_name' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        if (! $ip || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $result;
        }

        // 预留：MaxMind GeoLite2 本地库优先
        if (config('services.geoip.mmdb_path') && is_file(config('services.geoip.mmdb_path'))) {
            // TODO: 接入 maxmind-db/reader（Phase 4）
        }

        return $result;
    }

    /**
     * 国家代码 -> 大洲代码映射（ISO 3166）
     */
    public static function continentFromCountry(?string $countryCode): ?string
    {
        if (! $countryCode) {
            return null;
        }

        $map = [
            'AS' => ['CN', 'JP', 'KR', 'KP', 'IN', 'ID', 'VN', 'TH', 'PH', 'MY', 'SG', 'HK', 'TW', 'MO', 'MN', 'KH', 'LA', 'MM', 'BD', 'PK', 'LK', 'NP', 'BT', 'BN', 'TL', 'MV', 'AF', 'IR', 'IQ', 'SA', 'AE', 'IL', 'SY', 'LB', 'JO', 'YE', 'OM', 'KW', 'QA', 'BH', 'TR', 'AM', 'AZ', 'GE', 'KZ', 'UZ', 'TM', 'KG', 'TJ', 'PS'],
            'EU' => ['GB', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE', 'LU', 'IE', 'DK', 'SE', 'NO', 'FI', 'IS', 'AT', 'CH', 'PT', 'GR', 'PL', 'CZ', 'SK', 'HU', 'RO', 'BG', 'HR', 'SI', 'BA', 'RS', 'ME', 'MK', 'AL', 'LT', 'LV', 'EE', 'UA', 'BY', 'MD', 'CY', 'MT'],
            'NA' => ['US', 'CA', 'MX', 'GT', 'BZ', 'SV', 'HN', 'NI', 'CR', 'PA', 'CU', 'JM', 'HT', 'DO', 'BS', 'BB', 'TT'],
            'SA' => ['BR', 'AR', 'CL', 'CO', 'PE', 'VE', 'EC', 'BO', 'PY', 'UY', 'GY', 'SR'],
            'AF' => ['NG', 'EG', 'ZA', 'KE', 'GH', 'MA', 'DZ', 'TN', 'ET', 'TZ', 'UG', 'ZW', 'ZM', 'BW', 'NA', 'MZ', 'AO', 'CM', 'CI', 'SN', 'ML', 'NE', 'TD', 'SD', 'RW', 'MW', 'MG', 'SO', 'LY', 'CG', 'CD', 'GA', 'BF', 'BJ', 'TG', 'SL', 'LR', 'GM', 'GN', 'GW', 'MR'],
            'OC' => ['AU', 'NZ', 'FJ', 'PG', 'SB', 'VU', 'WS', 'TO', 'KI', 'TV', 'NR', 'PW', 'FM', 'MH'],
        ];

        $countryCode = strtoupper($countryCode);

        foreach ($map as $continent => $countries) {
            if (in_array($countryCode, $countries, true)) {
                return $continent;
            }
        }

        return null;
    }
}
