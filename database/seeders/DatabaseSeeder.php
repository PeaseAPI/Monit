<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Database\Seeder;

/**
 * Monit 初始数据
 * - 内置套餐（free / pro），配额来自 config/monit.php plan_defaults
 * - 平台默认设置（settings 表 key-value）
 */
class DatabaseSeeder extends Seeder
{
        public function run(): void
    {
        $defaults = config('monit.plan_defaults');

        // ---------- 套餐 ----------
        $plans = [
            [
                'plan_id' => 'free',
                'name' => '免费版',
                'description' => '适合个人与小型站点，永久免费。',
                'prices' => ['monthly' => 0, 'yearly' => 0, 'lifetime' => 0],
                'settings' => $defaults,
                'order' => 1,
                'trial_days' => 0,
                'is_enabled' => true,
            ],
            [
                'plan_id' => 'pro',
                'name' => '专业版',
                'description' => '不限网站数，支持事件与回放，适合团队与商业站点。',
                'prices' => ['monthly' => 9, 'yearly' => 90, 'lifetime' => 240],
                'settings' => [
                    ...$defaults,
                    'websites_limit' => -1,
                    'sessions_events_limit' => -1,
                    'events_children_limit' => -1,
                    'sessions_replays_limit' => -1,
                    'websites_heatmaps_limit' => -1,
                    'websites_goals_limit' => -1,
                    'annotations_limit' => -1,
                    'domains_limit' => -1,
                    'teams_is_enabled' => true,
                    'websites_sessions_replays_is_enabled' => true,
                    'websites_email_reports_is_enabled' => true,
                ],
                'order' => 2,
                'trial_days' => 14,
                'is_enabled' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['plan_id' => $plan['plan_id']], $plan);
        }

        // ---------- 平台设置 ----------
        $settings = [
            'site_name' => 'Monit',
            'site_url' => config('app.url'),
            'default_language' => 'zh_CN',
            'default_timezone' => 'Asia/Shanghai',
            'user_registration_is_enabled' => true,
            'admin_user_registration_notification_is_enabled' => false,
            'email_verification_is_enabled' => false,
            'last_cron_execution' => now()->toISOString(),
            'items_per_page' => 25,
            'email_reports_is_enabled' => false,
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        cache()->forget('monit.settings');

        $this->command?->info('已写入套餐：free、pro；平台设置 '.count($settings).' 项。');

        // 演示数据
        $this->call(DemoDataSeeder::class);
    }
}

