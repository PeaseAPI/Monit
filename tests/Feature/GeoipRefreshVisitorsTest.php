<?php

namespace Tests\Feature;

use App\Models\LightweightEvent;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

/**
 * geoip:refresh-visitors：存量地理数据重刷（ip2region 中文省/市 + db-ip 英文名共存）
 * 关联：GeoipRefreshVisitors 命令、GeoIp、GeoipUpdateCommand
 */
class GeoipRefreshVisitorsTest extends TestCase
{
    use RefreshDatabase;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Owner',
            'email' => 'geoip-refresh@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['sessions_events_limit' => -1, 'websites_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_geoip_refresh_key',
            'name' => 'GeoIP Refresh Site',
            'scheme' => 'https',
            'host' => 'geoip-refresh.test',
            'tracking_type' => 'lightweight',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => false,
            'datetime' => now(),
        ]);
    }

    public function test_fails_without_any_geoip_database(): void
    {
        config([
            'services.geoip.ip2region_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.xdb'),
            'services.geoip.mmdb_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.mmdb'),
        ]);

        $this->artisanCmd('geoip:refresh-visitors')->assertExitCode(1);
    }

    public function test_refreshes_existing_visitors_and_events_to_chinese_names(): void
    {
        if (! $this->xdbAvailable()) {
            $this->markTestSkipped('本地未放置 ip2region xdb（storage/app/geoip/ip2region.xdb），跳过中文重刷断言');
        }

        // 旧数据形态：db-ip city lite 英文名（region_name 列 M30 之前的旧行此列为 null）
        $visitor = WebsiteVisitor::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => WebsiteVisitor::uuidToBinary(Uuid::uuid4()->toString()),
            'ip' => '223.5.5.5',
            'continent_code' => 'AS',
            'country_code' => 'CN',
            'region_name' => 'Zhejiang',
            'city_name' => 'Hangzhou',
            'date' => now(),
            'last_date' => now(),
        ]);

        $event = LightweightEvent::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid' => $visitor->visitor_uuid_binary,
            'type' => 'pageview',
            'path' => '/',
            'continent_code' => 'AS',
            'country_code' => 'CN',
            'region_name' => 'Zhejiang',
            'city_name' => 'Hangzhou',
            'date' => now(),
        ]);

        $this->artisanCmd('geoip:refresh-visitors')->assertSuccessful();

        $freshVisitor = $this->freshModel($visitor);
        $this->assertSame('CN', $freshVisitor->country_code);
        $this->assertSame('AS', $freshVisitor->continent_code);
        $this->assertNotNull($freshVisitor->region_name);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $freshVisitor->region_name));
        $this->assertNotNull($freshVisitor->city_name);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $freshVisitor->city_name));

        // 事件表（无 IP 列，经 visitor_uuid 关联）同步刷成中文
        $freshEvent = $this->freshModel($event);
        $this->assertNotNull($freshEvent->region_name);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $freshEvent->region_name));
        $this->assertNotNull($freshEvent->city_name);
        $this->assertSame(1, preg_match('/[\x{4e00}-\x{9fff}]/u', $freshEvent->city_name));
    }

    public function test_keeps_rows_without_geo_gain(): void
    {
        if (! $this->xdbAvailable()) {
            $this->markTestSkipped('本地未放置 ip2region xdb（storage/app/geoip/ip2region.xdb），跳过海外保留断言');
        }

        // xdb 存在、mmdb 指向缺失文件：海外 IP（8.8.8.8）无中文增益 → 新值非空才覆盖，旧值必须保留
        config(['services.geoip.mmdb_path' => storage_path('app/geoip/definitely-missing-'.uniqid().'.mmdb')]);

        $visitor = WebsiteVisitor::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid_binary' => WebsiteVisitor::uuidToBinary(Uuid::uuid4()->toString()),
            'ip' => '8.8.8.8',
            'continent_code' => 'NA',
            'country_code' => 'US',
            'region_name' => 'California',
            'city_name' => 'Mountain View',
            'date' => now(),
            'last_date' => now(),
        ]);

        $event = LightweightEvent::create([
            'website_id' => $this->website->website_id,
            'visitor_uuid' => $visitor->visitor_uuid_binary,
            'type' => 'pageview',
            'path' => '/',
            'continent_code' => 'NA',
            'country_code' => 'US',
            'region_name' => 'California',
            'city_name' => 'Mountain View',
            'date' => now(),
        ]);

        $this->artisanCmd('geoip:refresh-visitors')->assertSuccessful();

        $freshVisitor = $this->freshModel($visitor);
        $this->assertSame('Mountain View', $freshVisitor->city_name);
        $this->assertSame('California', $freshVisitor->region_name);
        $this->assertSame('US', $freshVisitor->country_code);

        $freshEvent = $this->freshModel($event);
        $this->assertSame('Mountain View', $freshEvent->city_name);
        $this->assertSame('California', $freshEvent->region_name);
    }

    protected function xdbAvailable(): bool
    {
        $path = config('services.geoip.ip2region_path');

        return is_string($path) && $path !== '' && is_file($path);
    }
}
