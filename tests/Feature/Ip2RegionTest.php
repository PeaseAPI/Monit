<?php

namespace Tests\Feature;

use App\Support\Ip2Region;
use Tests\TestCase;

/**
 * ip2region 中文省/市离线查询（中国 IP 补充数据源）
 * 关联：GeoIp（中国 IP 优先数据源）、GeoipUpdateCommand（xdb 下载）、GeoipRefreshVisitors（存量重刷）
 */
class Ip2RegionTest extends TestCase
{
    public function test_lookup_returns_null_without_xdb(): void
    {
        config(['services.geoip.ip2region_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.xdb')]);

        $this->assertNull(Ip2Region::lookup('223.5.5.5'));
        $this->assertNull(Ip2Region::lookup(null));
        $this->assertNull(Ip2Region::lookup('192.168.1.10'));
        $this->assertNull(Ip2Region::lookup('127.0.0.1'));
    }

    public function test_lookup_resolves_chinese_region_when_xdb_present(): void
    {
        $path = config('services.geoip.ip2region_path');

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            $this->markTestSkipped('本地未放置 ip2region xdb（storage/app/geoip/ip2region.xdb，可运行 geoip:update 下载），跳过真实查询断言');
        }

        // 阿里公共 DNS（浙江杭州）；断言中文省/市（CJK）而非硬编码地名——库更新可能微调段归属
        $result = Ip2Region::lookup('223.5.5.5');

        $this->assertNotNull($result);
        $this->assertSame('CN', $result['country_code']);
        $this->assertNotNull($result['region_name']);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $result['region_name']));
        $this->assertNotNull($result['city_name']);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $result['city_name']));
    }

    public function test_parse_region_standard_record(): void
    {
        $result = static::parseRegion('中国|浙江省|杭州市|电信|CN');

        $this->assertSame(['country_code' => 'CN', 'region_name' => '浙江省', 'city_name' => '杭州市'], $result);
    }

    public function test_parse_region_municipality_drops_duplicate_city(): void
    {
        // 直辖市省/市同名 → 市置空（展示层 CONCAT_WS 避免出现「北京市 · 北京市」）
        $result = static::parseRegion('中国|北京市|北京市|电信|CN');

        $this->assertNotNull($result);
        $this->assertSame('北京市', $result['region_name']);
        $this->assertNull($result['city_name']);
    }

    public function test_parse_region_uses_country_code_when_country_named_zero(): void
    {
        // 部分段国家列缺失（0 占位）但末位国家码可识别
        $result = static::parseRegion('0|香港|香港|0|HK');

        $this->assertNull($result, '非中国（无 CN 码）应回退 mmdb');
    }

    public function test_parse_region_returns_null_for_foreign_record(): void
    {
        $this->assertNull(static::parseRegion('美国|0|0|0|US'));
        $this->assertNull(static::parseRegion('日本|东京|东京|0|JP'));
    }

    public function test_parse_region_returns_null_when_region_and_city_missing(): void
    {
        // 专网/骨干网段无省市级数据 → 回退 mmdb（保留英文城市与经纬度）
        $this->assertNull(static::parseRegion('中国|0|0|0|CN'));
    }

    /**
     * 调用 protected 静态解析（纯函数，无 IO 依赖）
     *
     * @return array{country_code: string, region_name: ?string, city_name: ?string}|null
     */
    protected static function parseRegion(string $region): ?array
    {
        $method = new \ReflectionMethod(Ip2Region::class, 'parseRegion');
        $method->setAccessible(true);

        /** @var array{country_code: string, region_name: ?string, city_name: ?string}|null $result */
        $result = $method->invoke(null, $region);

        return $result;
    }
}
