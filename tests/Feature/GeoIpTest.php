<?php

namespace Tests\Feature;

use App\Services\GeoIp;
use App\Support\CountryNames;
use Tests\TestCase;

/**
 * GeoIP 本地 mmdb 解析 + 国名/国旗展示辅助
 * 关联：PixelTracker（写入 country_code）、dashboard 国家面板
 */
class GeoIpTest extends TestCase
{
    public function test_lookup_returns_nulls_without_database(): void
    {
        config(['services.geoip.mmdb_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.mmdb')]);

        $result = (new GeoIp)->lookup('8.8.8.8');

        $this->assertNull($result['country_code']);
        $this->assertNull($result['continent_code']);
        $this->assertNull($result['region_name']);
        $this->assertNull($result['city_name']);
    }

    public function test_lookup_ignores_private_and_reserved_ips(): void
    {
        config(['services.geoip.mmdb_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.mmdb')]);

        $geoIp = new GeoIp;

        $this->assertNull($geoIp->lookup('127.0.0.1')['country_code']);
        $this->assertNull($geoIp->lookup('192.168.1.10')['country_code']);
        $this->assertNull($geoIp->lookup('10.0.0.1')['country_code']);
        $this->assertNull($geoIp->lookup(null)['country_code']);
    }

    public function test_lookup_resolves_public_ip_when_database_present(): void
    {
        $path = storage_path('app/geoip/country.mmdb');

        if (! is_file($path)) {
            $this->markTestSkipped('本地未放置 mmdb 库（storage/app/geoip/country.mmdb），跳过真实查询断言');
        }

        config(['services.geoip.mmdb_path' => $path]);

        $result = (new GeoIp)->lookup('8.8.8.8');

        $this->assertSame('US', $result['country_code']);
        $this->assertSame('NA', $result['continent_code']);
    }

    public function test_lookup_prefers_ip2region_chinese_names_for_china_ip(): void
    {
        $xdb = config('services.geoip.ip2region_path');

        if (! is_string($xdb) || $xdb === '' || ! is_file($xdb)) {
            $this->markTestSkipped('本地未放置 ip2region xdb（storage/app/geoip/ip2region.xdb），跳过中文省市断言');
        }

        // mmdb 指向缺失文件：证明中国 IP 的省/市来自 ip2region 而非 mmdb
        config(['services.geoip.mmdb_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.mmdb')]);

        $result = (new GeoIp)->lookup('223.5.5.5');

        $this->assertSame('CN', $result['country_code']);
        $this->assertSame('AS', $result['continent_code']);
        $this->assertNotNull($result['region_name']);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $result['region_name']), '省份应为中文（ip2region）');
        $this->assertNotNull($result['city_name']);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $result['city_name']), '城市应为中文（ip2region）');
    }

    public function test_continent_fallback_mapping(): void
    {
        $this->assertSame('AS', GeoIp::continentFromCountry('CN'));
        $this->assertSame('EU', GeoIp::continentFromCountry('DE'));
        $this->assertNull(GeoIp::continentFromCountry(null));
    }

    public function test_country_names_and_flags(): void
    {
        $this->assertSame('中国', CountryNames::name('CN', 'zh_CN'));
        $this->assertSame('China', CountryNames::name('CN', 'en'));
        $this->assertSame('ZZ', CountryNames::name('ZZ', 'zh_CN')); // 未收录回退原码
        $this->assertSame('🇨🇳', CountryNames::flag('CN'));
        $this->assertSame('🇺🇸', CountryNames::flag('us'));
        $this->assertSame('', CountryNames::flag(null));
        $this->assertSame('', CountryNames::flag('X1'));
    }
}
