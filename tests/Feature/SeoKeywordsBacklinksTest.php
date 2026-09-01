<?php

namespace Tests\Feature;

use App\Models\SeoBacklink;
use App\Models\SeoKeyword;
use App\Models\SeoKeywordRank;
use App\Models\User;
use App\Models\Website;
use App\Services\Seo\BacklinkChecker;
use App\Services\Seo\RankTracker;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SEO 关键词排名跟踪 + 反链分析（M26 扩展）
 * 关联：RankTracker（SerpApi 自动/手动快照）、BacklinkChecker（源页抓取活性验证）
 */
class SeoKeywordsBacklinksTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Website $website;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Seo Owner',
            'email' => 'seo-owner@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1, 'seo_keywords_limit' => -1],
        ]);

        $this->website = Website::create([
            'user_id' => $this->user->user_id,
            'pixel_key' => 'px_seo_kw_key',
            'name' => 'Seo Site',
            'scheme' => 'https',
            'host' => 'seo-target.test',
            'tracking_type' => 'lightweight',
            'is_enabled' => true,
            'bot_exclusion_is_enabled' => false,
            'query_parameters_tracking_is_enabled' => false,
            'datetime' => now(),
        ]);
    }

    protected function makeKeyword(array $overrides = []): SeoKeyword
    {
        return SeoKeyword::create(array_merge([
            'user_id' => $this->user->user_id,
            'website_id' => $this->website->website_id,
            'keyword' => 'monit 统计',
            'search_engine' => 'google',
            'device' => 'desktop',
            'locale' => 'zh-CN',
            'check_interval' => 'weekly',
            'is_enabled' => true,
        ], $overrides));
    }

    public function test_manual_snapshots_update_aggregates(): void
    {
        $tracker = app(RankTracker::class);
        $keyword = $this->makeKeyword();

        $tracker->record($keyword, 12);
        $tracker->record($keyword, 5);

        $keyword->refresh();

        $this->assertSame(5, $keyword->last_position);
        $this->assertSame(12, $keyword->previous_position);
        $this->assertSame(5, $keyword->best_position);
        $this->assertSame(7, $keyword->delta); // previous(12) - last(5) = 上升 7
        $this->assertSame(2, SeoKeywordRank::where('seo_keyword_id', $keyword->seo_keyword_id)->count());
    }

    public function test_serpapi_check_matches_website_host(): void
    {
        Settings::set('seo.serpapi_api_key', 'test-key');

        Http::fake([
            'serpapi.com/*' => Http::response([
                'organic_results' => [
                    ['position' => 1, 'link' => 'https://example.org/a'],
                    ['position' => 2, 'link' => 'https://www.seo-target.test/landing'],
                    ['position' => 3, 'link' => 'https://example.com/c'],
                ],
            ]),
        ]);

        $keyword = $this->makeKeyword();
        $rank = app(RankTracker::class)->check($keyword);

        $this->assertSame(2, $rank->position);
        $this->assertSame('https://www.seo-target.test/landing', $rank->url_found);
        $this->assertSame('auto', $rank->source);

        $keyword->refresh();
        $this->assertSame(2, $keyword->last_position);
    }

    public function test_keywords_page_renders_summary_and_rows(): void
    {
        $keyword = $this->makeKeyword(['keyword' => 'rank magic word']);
        app(RankTracker::class)->record($keyword, 2);

        $html = $this->actingAs($this->user)
            ->get(route('seo.keywords'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('rank magic word', $html);
        $this->assertStringContainsString('seo-target.test', $html);
    }

    public function test_keyword_store_rejects_duplicates(): void
    {
        $payload = ['keyword' => 'dup word', 'website_id' => $this->website->website_id];

        $this->actingAs($this->user)->post(route('seo.keywords.store'), $payload)->assertRedirect();
        $this->actingAs($this->user)->post(route('seo.keywords.store'), $payload)->assertSessionHasErrors('keyword');

        $this->assertSame(1, SeoKeyword::where('keyword', 'dup word')->count());
    }

    public function test_keyword_snapshot_denied_for_other_user(): void
    {
        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => bcrypt('secret123'),
            'type' => 0,
            'status' => 1,
            'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1],
        ]);

        $keyword = $this->makeKeyword();

        $this->actingAs($intruder)
            ->post(route('seo.keywords.snapshot', ['keyword' => $keyword->seo_keyword_id]), ['position' => 1])
            ->assertForbidden();
    }

    public function test_backlink_verify_active_and_lost(): void
    {
        Http::fake([
            'https://referrer.test/with-link' => Http::response('<p>Read <a href="https://seo-target.test/guide" rel="ugc">great guide</a> now</p>'),
            'https://referrer.test/without-link' => Http::response('<p>no link here</p>'),
        ]);

        $active = SeoBacklink::create([
            'user_id' => $this->user->user_id,
            'website_id' => $this->website->website_id,
            'source_url' => 'https://referrer.test/with-link',
            'source_host' => 'referrer.test',
            'target_url' => 'https://seo-target.test/',
            'rel' => 'unknown',
            'status' => 'pending',
            'first_seen_at' => now(),
        ]);

        $lost = SeoBacklink::create([
            'user_id' => $this->user->user_id,
            'website_id' => $this->website->website_id,
            'source_url' => 'https://referrer.test/without-link',
            'source_host' => 'referrer.test',
            'target_url' => 'https://seo-target.test/',
            'rel' => 'unknown',
            'status' => 'pending',
            'first_seen_at' => now(),
        ]);

        $checker = app(BacklinkChecker::class);

        $this->assertSame('active', $checker->verify($active));
        $active->refresh();
        $this->assertSame('dofollow', $active->rel); // rel="ugc" 不含 nofollow → dofollow
        $this->assertSame('great guide', $active->anchor_text);

        $this->assertSame('lost', $checker->verify($lost));
        $this->assertSame('lost', $lost->refresh()->status);
    }

    public function test_backlinks_page_renders_summary(): void
    {
        SeoBacklink::create([
            'user_id' => $this->user->user_id,
            'website_id' => $this->website->website_id,
            'source_url' => 'https://blog-one.test/post',
            'source_host' => 'blog-one.test',
            'rel' => 'dofollow',
            'status' => 'active',
            'first_seen_at' => now(),
        ]);

        $html = $this->actingAs($this->user)
            ->get(route('seo.backlinks'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('blog-one.test/post', $html);
        $this->assertStringContainsString('Dofollow', $html);
    }
}
