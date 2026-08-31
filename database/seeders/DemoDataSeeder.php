<?php

namespace Database\Seeders;

use App\Models\Annotation;
use App\Models\Domain;
use App\Models\Heatmap;
use App\Models\InternalNotification;
use App\Models\OutboundClick;
use App\Models\Payment;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\Website;
use App\Models\WebsiteGoal;
use App\Models\WebsiteVisitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;

/**
 * Monit 演示数据填充
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@monit.dev'],
            [
                'name' => '管理员', 'password' => Hash::make('password'),
                'type' => 1, 'plan_id' => 'pro', 'status' => 1,
                'api_key' => bin2hex(random_bytes(32)),
                'referral_key' => 'admin_ref_'.bin2hex(random_bytes(8)),
                'language' => 'zh_CN', 'timezone' => 'Asia/Shanghai',
                'total_logins' => 42, 'last_activity' => now(),
            ]
        );

        $proUser = User::updateOrCreate(
            ['email' => 'pro@monit.dev'],
            [
                'name' => '专业用户', 'password' => Hash::make('password'),
                'type' => 0, 'plan_id' => 'pro',
                'plan_expiration_date' => now()->addYear(),
                'status' => 1, 'api_key' => bin2hex(random_bytes(32)),
                'referral_key' => 'pro_ref_'.bin2hex(random_bytes(8)),
                'language' => 'zh_CN', 'timezone' => 'Asia/Shanghai',
                'total_logins' => 18, 'last_activity' => now()->subHours(2),
                'country' => 'China', 'city_name' => 'Shanghai',
            ]
        );

        $freeUser = User::updateOrCreate(
            ['email' => 'free@monit.dev'],
            [
                'name' => '免费用户', 'password' => Hash::make('password'),
                'type' => 0, 'plan_id' => 'free', 'status' => 1,
                'api_key' => bin2hex(random_bytes(32)),
                'referral_key' => 'free_ref_'.bin2hex(random_bytes(8)),
                'language' => 'zh_CN', 'timezone' => 'Asia/Shanghai',
                'total_logins' => 5, 'last_activity' => now()->subDays(3),
                'referred_by' => $proUser->user_id,
                'country' => 'China', 'city_name' => 'Beijing',
            ]
        );

        $this->command?->info('✅ 用户：admin / pro / free（密码均为 password）');
        $this->seedWebsites($proUser, $freeUser);
    }

    protected function seedWebsites(User $proUser, User $freeUser): void
    {
        $websites = collect();
        $siteData = [
            ['name' => '主站', 'host' => 'example.com'],
            ['name' => '文档站', 'host' => 'docs.example.com'],
            ['name' => '博客', 'host' => 'blog.example.com'],
        ];

        foreach ($siteData as $data) {
            $w = Website::updateOrCreate(
                ['host' => $data['host'], 'user_id' => $proUser->user_id],
                [
                    'name' => $data['name'], 'scheme' => 'https',
                    'pixel_key' => 'px_'.bin2hex(random_bytes(16)),
                    'tracking_type' => 'advanced', 'is_enabled' => true,
                    'last_24_hours_pageviews' => rand(80, 500),
                    'last_7_days_pageviews' => rand(1000, 5000),
                    'current_month_sessions_events' => rand(5000, 20000),
                    'timezone' => 'Asia/Shanghai',
                ]
            );
            $websites->push($w);
        }

        $freeW = Website::updateOrCreate(
            ['host' => 'mysite.test', 'user_id' => $freeUser->user_id],
            [
                'name' => '我的小站', 'scheme' => 'https',
                'pixel_key' => 'px_'.bin2hex(random_bytes(16)),
                'tracking_type' => 'lightweight', 'is_enabled' => true,
                'last_24_hours_pageviews' => rand(10, 50),
                'last_7_days_pageviews' => rand(100, 300),
                'current_month_sessions_events' => rand(200, 800),
                'timezone' => 'Asia/Shanghai',
            ]
        );

        $this->command?->info('✅ 网站：4 个（pro×3 + free×1）');
        $this->seedVisitors($websites[0]);
        $this->seedGoals($websites[0], $proUser);
        $this->seedPayments($proUser, $freeUser);
        $this->seedTeams($proUser, $freeUser);
        $this->seedMisc($proUser, $websites[0], $freeUser);
    }

    protected function seedVisitors(Website $site): void
    {
        $countries = ['CN', 'US', 'JP', 'DE', 'GB', 'FR', 'KR', 'SG'];
        $devices = ['desktop', 'mobile', 'tablet'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge'];
        $oses = ['Windows', 'macOS', 'Linux', 'Android', 'iOS'];

        for ($day = 0; $day < 7; $day++) {
            $date = now()->subDays($day)->startOfDay();
            for ($i = 0; $i < rand(50, 150); $i++) {
                $uuid = Uuid::uuid4();
                WebsiteVisitor::create([
                    'website_id' => $site->website_id,
                    'visitor_uuid_binary' => $uuid->getBytes(),
                    'ip' => long2ip(rand(0, 4294967295)),
                    'country_code' => $countries[array_rand($countries)],
                    'device_type' => $devices[array_rand($devices)],
                    'os_name' => $oses[array_rand($oses)],
                    'browser_name' => $browsers[array_rand($browsers)],
                    'date' => $date,
                    'last_date' => $date,
                    'total_sessions' => rand(1, 5),
                ]);
            }
        }
        $this->command?->info('✅ 访客：约 '.WebsiteVisitor::where('website_id', $site->website_id)->count().' 条');
    }

    protected function seedGoals(Website $site, User $user): void
    {
        $goals = [
            ['name' => '注册完成', 'type' => 'pageview', 'path' => '/register/success', 'key' => 'signup'],
            ['name' => '购买确认', 'type' => 'custom', 'path' => 'purchase_complete', 'key' => 'purchase'],
            ['name' => '联系表单提交', 'type' => 'pageview', 'path' => '/contact/thanks', 'key' => 'contact'],
        ];
        foreach ($goals as $g) {
            WebsiteGoal::updateOrCreate(
                ['website_id' => $site->website_id, 'key' => $g['key']],
                ['name' => $g['name'], 'type' => $g['type'], 'path' => $g['path']]
            );
        }

        $annotations = [
            ['date' => now()->subDays(5)->format('Y-m-d'), 'name' => '首页改版上线'],
            ['date' => now()->subDays(3)->format('Y-m-d'), 'name' => '开始投放 Google Ads'],
            ['date' => now()->subDays(1)->format('Y-m-d'), 'name' => '服务器扩容'],
        ];
        foreach ($annotations as $a) {
            Annotation::updateOrCreate(
                ['website_id' => $site->website_id, 'date' => $a['date'], 'user_id' => $user->user_id],
                ['name' => $a['name']]
            );
        }

        $outLinks = [
            ['host' => 'github.com', 'path' => '/'],
            ['host' => 'laravel.com', 'path' => '/docs'],
            ['host' => 'tailwindcss.com', 'path' => '/'],
            ['host' => 'stripe.com', 'path' => '/pricing'],
        ];
        foreach ($outLinks as $link) {
            OutboundClick::create([
                'website_id' => $site->website_id,
                'host' => $link['host'],
                'path' => $link['path'],
                'title' => $link['host'],
                'datetime' => now()->subDays(rand(0, 6)),
            ]);
        }

        Heatmap::create([
            'website_id' => $site->website_id,
            'path' => '/', 'name' => '首页热图',
            'is_enabled' => true, 'datetime' => now(),
        ]);

        $this->command?->info('✅ 目标：3 / 标注：3 / 出站点击：4 / 热图：1');
    }

    protected function seedPayments(User $proUser, User $freeUser): void
    {
        $payments = [
            ['user_id' => $proUser->user_id, 'type' => 'one_time', 'frequency' => 'yearly', 'total_amount' => 90.00],
            ['user_id' => $proUser->user_id, 'type' => 'recurring', 'frequency' => 'monthly', 'total_amount' => 9.00],
            ['user_id' => $freeUser->user_id, 'type' => 'one_time', 'frequency' => 'lifetime', 'total_amount' => 0],
        ];

        foreach ($payments as $p) {
            $u = User::find($p['user_id']);
            Payment::create([
                'user_id' => $p['user_id'], 'name' => $u->name, 'email' => $u->email,
                'payment_processor' => 'stripe', 'type' => $p['type'],
                'frequency' => $p['frequency'], 'total_amount' => $p['total_amount'],
                'currency' => 'USD', 'status' => 1,
                'datetime' => now()->subDays(rand(1, 30)),
            ]);
        }
        $this->command?->info('✅ 支付：3 条');
    }

    protected function seedTeams(User $proUser, User $freeUser): void
    {
        $team = Team::updateOrCreate(
            ['user_id' => $proUser->user_id, 'name' => '产品团队'],
            ['datetime' => now()]
        );

        TeamMember::updateOrCreate(
            ['team_id' => $team->team_id, 'user_email' => $freeUser->email],
            [
                'user_id' => $freeUser->user_id, 'is_owned' => false,
                'access' => ['read'], 'status' => 1,
                'datetime' => now(), 'last_activity' => now()->subHours(5),
            ]
        );

        TeamMember::updateOrCreate(
            ['team_id' => $team->team_id, 'user_email' => 'invited@example.com'],
            [
                'user_id' => null, 'is_owned' => false,
                'access' => ['read'], 'status' => 0, 'datetime' => now(),
            ]
        );
        $this->command?->info('✅ 团队：1 个（含 2 成员）');
    }

    protected function seedMisc(User $proUser, Website $site, User $freeUser): void
    {
        Domain::updateOrCreate(
            ['user_id' => $proUser->user_id, 'host' => 'cdn.example.com'],
            ['scheme' => 'https', 'is_enabled' => true, 'datetime' => now()]
        );

        InternalNotification::create([
            'user_id' => $proUser->user_id, 'for_type' => 'team_invite',
            'data' => json_encode(['message' => '您已被邀请加入团队']),
            'is_read' => false, 'datetime' => now()->subHours(1),
        ]);

        InternalNotification::create([
            'user_id' => User::where('type', 1)->first()->user_id,
            'for_type' => 'new_user',
            'data' => json_encode(['message' => '新用户注册：free@monit.dev']),
            'is_read' => true, 'datetime' => now()->subDays(2),
        ]);

        $this->command?->info('✅ 域名：1 / 通知：2');

        $this->command?->info('');
        $this->command?->info('🎉 演示数据填充完成！');
        $this->command?->info('管理员: admin@monit.dev / password');
        $this->command?->info('专业用户: pro@monit.dev / password');
        $this->command?->info('免费用户: free@monit.dev / password');
    }
}
