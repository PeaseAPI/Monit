<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Database\Seeder;

/**
 * 生产配置导入（提取自 www_monit_cn.sql —— 2026-08 线上库快照）
 *
 * 用途：把线上真实商业配置（三档定价 / 税费 / 品牌备案）灌入本地或新部署实例，
 * 免去手工在后台逐项录入。幂等（updateOrCreate），可重复执行。
 *
 * 执行：php artisan db:seed --class=ProductionSeeder --force
 *
 * 内容对应原库：
 * - plans #1 Plus版 / #2 Pro版 / #3 Ultra版（CNY+USD 双币月付/年付，Plus 试用 7 天）
 *   prices 已从原库形态 {monthly:{CNY:9}} 转为项目形态 {CNY:{monthly:9}}（Currency::planPrice 直配价）
 * - taxes #1 技术服务费(普票) 6% inclusive / #2 技术研发费 6% inclusive
 *   （原库 billing_type=both，当前枚举仅 personal/business，按发票主场景分别取 business / personal）
 * - settings：品牌备案号等生产值（仅覆盖当前架构已有的扁平 key）
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            Plan::updateOrCreate(['plan_id' => $plan['plan_id']], $plan);
        }

        foreach ($this->taxes() as $tax) {
            Tax::updateOrCreate(['name' => $tax['name']], $tax);
        }

        foreach ($this->settings() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        cache()->forget('monit.settings');

        $this->command?->info('已导入生产配置：套餐 plus/pro/ultra（CNY+USD 双币）、税费 2 项、品牌设置项。');
    }

    /** @return array<int, array<string, mixed>> */
    protected function plans(): array
    {
        return [
            [
                'plan_id' => 'plus',
                'name' => 'Plus',
                'description' => '适用于个人用户，网站≤5的用户。无会话回放等功能。',
                'prices' => [
                    'CNY' => ['monthly' => 9, 'annual' => 99],
                    'USD' => ['monthly' => 1.9, 'annual' => 19],
                ],
                'settings' => $this->quota(
                    websitesLimit: 5, eventsRetention: 90,
                    replaysLimit: 0, replaysRetention: 30, replaysTime: 1, teams: false,
                ),
                'order' => 2,
                'trial_days' => 7,
                'taxes_ids' => [1, 2],
                'is_enabled' => true,
            ],
            [
                'plan_id' => 'pro',
                'name' => 'Pro',
                'description' => '适合中小企业或第三方运维团队等。同时运营多网站。',
                'prices' => [
                    'CNY' => ['monthly' => 29, 'annual' => 299],
                    'USD' => ['monthly' => 4.9, 'annual' => 49],
                ],
                'settings' => $this->quota(
                    websitesLimit: 20, eventsRetention: 183,
                    replaysLimit: -1, replaysRetention: 90, replaysTime: 3, teams: true,
                ),
                'order' => 3,
                'trial_days' => 0,
                'taxes_ids' => [1, 2],
                'is_enabled' => true,
            ],
            [
                'plan_id' => 'ultra',
                'name' => 'Ultra',
                'description' => '无限制版本。',
                'prices' => [
                    'CNY' => ['monthly' => 99, 'annual' => 999],
                    'USD' => ['monthly' => 19, 'annual' => 159],
                ],
                'settings' => $this->quota(
                    websitesLimit: -1, eventsRetention: 365,
                    replaysLimit: -1, replaysRetention: 365, replaysTime: 5, teams: true,
                ),
                'order' => 4,
                'trial_days' => 0,
                'taxes_ids' => [1, 2],
                'is_enabled' => true,
            ],
        ];
    }

    /**
     * 套餐配额（键名与 config('monit.plan_defaults') 完全对齐，原库 plans.settings 提取）
     *
     * @return array<string, mixed>
     */
    protected function quota(
        int $websitesLimit,
        int $eventsRetention,
        int $replaysLimit,
        int $replaysRetention,
        int $replaysTime,
        bool $teams,
    ): array {
        return [
            'no_ads' => true,
            'email_reports_is_enabled' => true,
            'teams_is_enabled' => $teams,
            'websites_limit' => $websitesLimit,
            'sessions_events_limit' => -1,
            'events_children_limit' => -1,
            'events_children_retention' => $eventsRetention,
            'sessions_replays_limit' => $replaysLimit,
            'sessions_replays_retention' => $replaysRetention,
            'sessions_replays_time_limit' => $replaysTime,
            'websites_heatmaps_limit' => -1,
            'websites_goals_limit' => -1,
            'domains_limit' => -1,
            'api_is_enabled' => true,
            'affiliate_commission_percentage' => 30,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function taxes(): array
    {
        $now = now();

        return [
            [
                'name' => '技术服务费',
                'description' => '技术服务费(普票)',
                'value' => 6,
                'value_type' => 'percentage',
                'type' => 'inclusive',
                'billing_type' => 'business',
                'countries' => ['CN'],
                'datetime' => $now,
            ],
            [
                'name' => '技术研发费',
                'description' => '技术研发费(普票)',
                'value' => 6,
                'value_type' => 'percentage',
                'type' => 'inclusive',
                'billing_type' => 'personal',
                'countries' => ['CN'],
                'datetime' => $now,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function settings(): array
    {
        return [
            // 页脚备案号（原库 pages #7，链接工信部备案系统）
            'branding.footer_icp' => '冀ICP备18013359号-38',
            // 落地页展示定价区（原库 main.display_index_plans = true）
            'branding.show_landing_plans' => 'true',
            // SEO 功能总闸与访客开放（修复线上 403：原库快照无 seo.* 键，缺失时
            // SeoGuestAccess / SeoAuditController::analyze() 会 403 拒绝访客，
            // 与原站首页"免费 SEO 分析"获客组件冲突，故按原站行为显式开启）
            'seo.audits_is_enabled' => 'true',
            'seo.tools_is_enabled' => 'true',
            'seo.tools_guest_access' => 'true',
        ];
    }
}
