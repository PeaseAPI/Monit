<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * M5 用户中心冒烟（规格 §6.2）：登录用户核心页面可达性
 */
class UserCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::create([
            'name' => 'Admin', 'email' => 'admin@test.dev',
            'password' => bcrypt('secret123'), 'type' => 1,
            'status' => 1, 'plan_id' => 'custom',
            'plan_settings' => ['websites_limit' => -1],
        ]);
    }

    public static function userPages(): array
    {
        return [
            'dashboard' => ['/dashboard'],
            'websites' => ['/websites'],
            'website-create' => ['/websites/create'],
            'domains' => ['/domains'],
            'teams' => ['/teams'],
            'account' => ['/account'],
            'account-plan' => ['/account-plan'],
            'account-preferences' => ['/account-preferences'],
            'account-logs' => ['/account/logs'],
            'account-payments' => ['/account-payments'],
            'billing' => ['/pay-billing'],
            'notifications' => ['/notifications'],
            'referrals' => ['/referrals'],
        ];
    }

    #[DataProvider('userPages')]
    public function test_user_center_page_renders(string $uri): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->get($uri)->assertOk();
    }

    public function test_stats_subpages_render_with_website(): void
    {
        $user = $this->adminUser();

        $website = \App\Models\Website::create([
            'user_id' => $user->user_id,
            'pixel_key' => 'px_uc_1', 'name' => 'UC Site',
            'scheme' => 'https', 'host' => 'uc.test',
            'tracking_type' => 'advanced', 'is_enabled' => true,
            'excluded_ips' => '', 'datetime' => now(),
        ]);

        foreach (['stats/'.$website->website_id, 'stats/'.$website->website_id.'/goals', 'stats/'.$website->website_id.'/annotations', 'stats/'.$website->website_id.'/heatmaps', 'stats/'.$website->website_id.'/replays'] as $uri) {
            $this->actingAs($user)->get($uri)->assertOk("GET {$uri} 应返回 200");
        }
    }

    public function test_api_key_roundtrip(): void
    {
        $user = $this->adminUser();

        $this->assertNull($user->api_key);

        $response = $this->actingAs($user)->put('/account/api-key', []);
        $response->assertRedirect();

        $this->assertNotNull($user->refresh()->api_key);
        $this->assertEquals(64, strlen((string) $user->api_key));
    }
}
