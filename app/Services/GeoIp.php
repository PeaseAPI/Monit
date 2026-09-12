<?php

namespace App\Services;

use App\Support\Ip2Region;
use App\Support\Typed;
use MaxMind\Db\Reader;

/**
 * Monit GeoIP 地理位置解析
 *
 * 本地 MaxMind mmdb 库查询（GeoLite2 与 db-ip city lite 格式兼容）：
 * - 库路径由 config('services.geoip.mmdb_path') 指定，默认 storage/app/geoip/country.mmdb
 * - **必须使用 City（城市）库**：country 库无 city 字段，位置只能识别到国家。
 *   免费城市库下载（免注册，每月更新）：
 *   curl -L https://download.db-ip.com/free/dbip-city-lite-$(date +%Y-%m).mmdb.gz \
 *     | gunzip > storage/app/geoip/country.mmdb
 * - City 库记录含 city.names / location；无城市判定的 IP（CDN/数据中心）自动
 *   回退为仅显示国家——满足「优先城市、无法判定才只显示国家」的展示要求
 * - 未放置库文件时静默返回空结果（国家显示为未知，不影响采集）
 *
 * 中国 IP 中文省/市（ip2region）：db-ip 免费库的 city/subdivisions 只有英文名
 * （names 无 zh-CN），中国访客省市会显示 Beijing/Hangzhou 等拼音。接入
 * ip2region 离线中文库（storage/app/geoip/ip2region.xdb，geoip:update 自动
 * 下载）后，中国 IP 且能给出省/市时优先采用其结果，其余情况回退 mmdb。
 *
 * 关联：PixelTracker（写入 continent_code/country_code）、CountryNames（展示层国名/国旗）、
 *       App\Support\Ip2Region（中国 IP 中文省/市）
 */
class GeoIp
{
    protected ?Reader $reader = null;

    protected bool $readerFailed = false;

    /**
     * @return array{continent_code: ?string, country_code: ?string, region_name: ?string, city_name: ?string, latitude: ?float, longitude: ?float}
     */
    public function lookup(?string $ip): array
    {
        $result = [
            'continent_code' => null,
            'country_code' => null,
            'region_name' => null,
            'city_name' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        if (($ip === null || $ip === '') || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return $result;
        }

        // 中国 IP 优先 ip2region：db-ip city lite 无中文名（names 无 zh-CN），
        // ip2region 直接给出中文省/市（如 浙江省/杭州市）；未命中时回退 mmdb
        $cn = Ip2Region::lookup($ip);

        if ($cn !== null) {
            $result['country_code'] = $cn['country_code'];
            $result['continent_code'] = static::continentFromCountry($cn['country_code']);
            $result['region_name'] = $cn['region_name'];
            $result['city_name'] = $cn['city_name'];

            return $result;
        }

        $record = $this->lookupRecord($ip);

        if ($record !== null) {
            $result['country_code'] = Typed::stringOrNull(data_get($record, 'country.iso_code'));
            $result['continent_code'] = Typed::stringOrNull(data_get($record, 'continent.code'))
                ?? static::continentFromCountry($result['country_code']);
            // 省/州（city 库 subdivisions 数组的第一项为最高级行政区，中国即省级）
            $result['region_name'] = static::firstNonEmpty(
                data_get($record, 'subdivisions.0.names.zh-CN'),
                data_get($record, 'subdivisions.0.names.en'),
                data_get($record, 'subdivisions.0.iso_code'),
            );
            $result['city_name'] = static::firstNonEmpty(
                data_get($record, 'city.names.zh-CN'),
                data_get($record, 'city.names.en'),
            );
            $latitude = data_get($record, 'location.latitude');
            $longitude = data_get($record, 'location.longitude');
            $result['latitude'] = $latitude === null ? null : Typed::float($latitude);
            $result['longitude'] = $longitude === null ? null : Typed::float($longitude);
        }

        return $result;
    }

    /**
     * 取首个非空标量值（mmdb 记录键缺失/非字符串时回退下一候选）
     */
    protected static function firstNonEmpty(mixed ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $value = Typed::stringOrNull($candidate);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * 查询 mmdb 记录（库缺失 / 打开失败 / 查询异常均静默返回 null）
     *
     * @return array<string, mixed>
     */
    protected function lookupRecord(string $ip): ?array
    {
        if ($this->readerFailed) {
            return null;
        }

        if ($this->reader === null) {
            $path = Typed::string(config('services.geoip.mmdb_path'));

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

        /** @var array<string, mixed>|null $record */
        $record = is_array($record) ? $record : null;

        return $record;
    }

    /**
     * 是否已配置可用的 mmdb 库（供管理面板状态提示）
     */
    public function isAvailable(): bool
    {
        $path = Typed::string(config('services.geoip.mmdb_path'));

        return $path !== '' && is_file($path);
    }

    /**
     * 国家代码 -> 大洲代码映射（ISO 3166）
     */
    public static function continentFromCountry(?string $countryCode): ?string
    {
        if (($countryCode === null || $countryCode === '')) {
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
