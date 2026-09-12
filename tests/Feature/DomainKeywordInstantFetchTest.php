<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\SeoKeyword;
use App\Models\SeoKeywordRank;
use App\Models\User;
use App\Models\Website;
use App\Services\Seo\DomainMonitor;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * 二十六轮：域名 WHOIS 添加即查（模型属性 null 陷阱）+ 详情页手动刷新
 * + SEO 关键词首次添加立即抓取（用户反馈：添加后啥信息都没有）
 */
class DomainKeywordInstantFetchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Instant Owner',
            'email' => 'instant-owner@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => [
                'websites_limit' => -1,
                'domains_limit' => -1,
                'seo_keywords_limit' => -1,
            ],
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_instant_kw',
            'name' => 'Instant Site',
            'scheme' => 'https',
            'host' => 'seo-target.test',
            'tracking_type' => 'lightweight',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => false,
            'datetime' => now(),
        ]);
    }

    public function test_domain_store_triggers_immediate_whois_refresh(): void
    {
        // 根因回归：此前 Domain::create 未显式 monitor_is_enabled，
        // 模型属性为 null（DB 默认 1 不回填）→ store 内 if 判定恒假，
        // 「添加即查 whois」从未执行（线上 domains/1 monitor_* 全空）
        $monitor = $this->mock(DomainMonitor::class);
        $monitor->shouldReceive('refresh')->once()->andReturn(365);

        $this->actingAs($this->user)
            ->post(route('domains.store'), ['host' => 'Example.COM'])
            ->assertRedirect(route('domains.index'));

        $domain = Domain::where('user_id', $this->user->user_id)->where('host', 'example.com')->firstOrFail();
        $this->assertTrue($domain->monitor_is_enabled);
    }

    public function test_domain_refresh_endpoint_returns_result(): void
    {
        $domain = Domain::create([
            'user_id' => $this->user->user_id,
            'host' => 'refreshable.com',
            'scheme' => 'https',
            'is_enabled' => true,
            'monitor_is_enabled' => true,
            'datetime' => now(),
        ]);

        $monitor = $this->mock(DomainMonitor::class);
        $monitor->shouldReceive('refresh')->once()->andReturn(180);

        $this->actingAs($this->user)
            ->post(route('domains.refresh', $domain->domain_id))
            ->assertRedirect()
            ->assertSessionHas('success');

        // 查询失败 → error 提示而非 500
        $monitor2 = $this->mock(DomainMonitor::class);
        $monitor2->shouldReceive('refresh')->once()->andReturn(null);

        $this->actingAs($this->user)
            ->post(route('domains.refresh', $domain->domain_id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_domain_refresh_blocks_other_users_domain(): void
    {
        $other = User::create([
            'name' => 'Other Owner',
            'email' => 'other-owner@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['domains_limit' => -1],
        ]);

        $domain = Domain::create([
            'user_id' => $other->user_id,
            'host' => 'not-yours.com',
            'scheme' => 'https',
            'is_enabled' => true,
            'monitor_is_enabled' => true,
            'datetime' => now(),
        ]);

        $this->actingAs($this->user)
            ->post(route('domains.refresh', $domain->domain_id))
            ->assertNotFound();
    }

    public function test_keyword_store_fetches_first_rank_immediately(): void
    {
        Settings::set('seo.serpapi_api_key', 'test-key');

        Http::fake([
            'serpapi.com/*' => Http::response([
                'organic_results' => [
                    ['position' => 1, 'link' => 'https://example.org/a'],
                    ['position' => 2, 'link' => 'https://www.seo-target.test/landing'],
                ],
            ]),
        ]);

        $this->actingAs($this->user)
            ->post(route('seo.keywords.store'), [
                'keyword' => '即时排名',
                'website_id' => $this->website->website_id,
                'search_engine' => 'google',
                'check_interval' => 'daily',
            ])
            ->assertRedirect();

        $keyword = SeoKeyword::where('user_id', $this->user->user_id)->where('keyword', '即时排名')->firstOrFail();
        $this->assertSame(2, $keyword->last_position);
        $this->assertSame(1, SeoKeywordRank::where('seo_keyword_id', $keyword->seo_keyword_id)->count());
    }

    public function test_keyword_store_without_serpapi_still_succeeds_without_rank(): void
    {
        $this->actingAs($this->user)
            ->post(route('seo.keywords.store'), [
                'keyword' => '未配置也成功',
                'website_id' => $this->website->website_id,
            ])
            ->assertRedirect();

        $keyword = SeoKeyword::where('keyword', '未配置也成功')->firstOrFail();
        $this->assertNull($keyword->last_position);
        $this->assertSame(0, SeoKeywordRank::where('seo_keyword_id', $keyword->seo_keyword_id)->count());
    }
}
