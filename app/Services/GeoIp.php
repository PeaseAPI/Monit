<?php

namespace App\Services;

use MaxMind\Db\Reader;

/**
 * Monit GeoIP 地理位置解析
 *
 * 本地 MaxMind mmdb 库查询（GeoLite2 与 db-ip country lite 格式兼容）：
 * - 库路径由 config('services.geoip.mmdb_path') 指定，默认 storage/app/geoip/country.mmdb
 * - 免费库下载（免注册，每月更新）：
 *   curl -L https://download.db-ip.com/free/dbip-country-lite-$(date +%Y-%m).mmdb.gz \
 *     | gunzip > storage/app/geoip/country.mmdb
 * - 未放置库文件时静默返回空结果（国家显示为未知，不影响采集）
 *
 * 关联：PixelTracker（写入 continent_code/country_code）、CountryNames（展示层国名/国旗）
 */
class GeoIp
{
    protected ?Reader $reader = null;

    protected bool $readerFailed = false;

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

        $record = $this->lookupRecord($ip);

        if ($record !== null) {
            $result['country_code'] = $record['country']['iso_code'] ?? null;
            $result['continent_code'] = $record['continent']['code']
                ?? static::continentFromCountry($result['country_code']);
            $result['city_name'] = $record['city']['names']['zh-CN']
                ?? $record['city']['names']['en']
                ?? null;
            $result['latitude'] = isset($record['location']['latitude']) ? (float) $record['location']['latitude'] : null;
            $result['longitude'] = isset($record['location']['longitude']) ? (float) $record['location']['longitude'] : null;
        }

        return $result;
    }

    /**
     * 查询 mmdb 记录（库缺失 / 打开失败 / 查询异常均静默返回 null）
     */
    protected function lookupRecord(string $ip): ?array
    {
        if ($this->readerFailed) {
            return null;
        }

        if ($this->reader === null) {
            $path = (string) config('services.geoip.mmdb_path');

            if ($path === '' || ! is_file($path)) {
                $this->readerFailed = true;

                return null;
            }

            try {
                $this->reader = new Reader($path);
            } catch (\Throwable) {
                $this->readerFailed = true;

                return null;
            }
        }

        try {
            $record = $this->reader->get($ip);
        } catch (\Throwable) {
            return null;
        }

        return is_array($record) ? $record : null;
    }

    /**
     * 是否已配置可用的 mmdb 库（供管理面板状态提示）
     */
    public function isAvailable(): bool
    {
        $path = (string) config('services.geoip.mmdb_path');

        return $path !== '' && is_file($path);
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
