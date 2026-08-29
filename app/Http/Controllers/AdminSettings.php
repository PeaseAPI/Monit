<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 系统设置（AdminSettings，94K 最大控制器）
 * 规格书 §6.3.1：集中管理全部 settings 配置
 *
 * 分组：main, payment, analytics, cron, webhooks, plan_free/guest/custom, socials, plugins
 */
class AdminSettings extends Controller
{
    /**
     * 设置主页面（所有分组选项卡）
     */
    public function index(Request $request)
    {
        $settings = $this->allSettings();

                return view('admin.settings.index', compact('settings'))->with('adminNav', 'settings');
    }

    /**
     * 通用更新入口：通过 group 参数确定分组
     */
    public function update(Request $request): RedirectResponse
    {
        $group = $request->input('group');
        $rules = $this->getValidationRules($group);

        if (empty($rules)) {
                        return back()->withErrors(['error' => __('msg.invalid_settings_group')]);
        }

        $validated = $request->validate($rules);
        $this->saveSettings($group, $validated);

        // 清除应用缓存
        \Illuminate\Support\Facades\Cache::forget('monit_settings');

                return back()->with('success', __('msg.settings_saved', ['group' => $group]));
    }

    /* --------------------------------------------------------------------- */
    /* 设置分组 & 保存                                                       */
    /* --------------------------------------------------------------------- */

    protected function allSettings(): array
    {
        return [
            'main' => $this->getGroup('main'),
            'payment' => $this->getGroup('payment'),
            'analytics' => $this->getGroup('analytics'),
            'cron' => $this->getGroup('cron'),
            'webhooks' => $this->getGroup('webhooks'),
            'socials' => $this->getGroup('socials'),
            'plan_free' => $this->getGroup('plan_free'),
            'plan_guest' => $this->getGroup('plan_guest'),
            'plan_custom' => $this->getGroup('plan_custom'),
        ];
    }

    protected function getGroup(string $group): array
    {
        return Setting::where('key', 'like', "{$group}.%")->pluck('value', 'key')->toArray();
    }

    protected function saveSettings(string $group, array $data): void
    {
        DB::transaction(function () use ($group, $data) {
            foreach ($data as $key => $value) {
                $fullKey = "{$group}.{$key}";
                Setting::updateOrCreate(
                    ['key' => $fullKey],
                    ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (json_encode($value, JSON_UNESCAPED_UNICODE))]
                );
            }
        });
    }

    /* --------------------------------------------------------------------- */
    /* 验证规则                                                              */
    /* --------------------------------------------------------------------- */

    protected function getValidationRules(string $group): array
    {
        $rules = match ($group) {
            'main' => [
                'site_title' => 'required|string|max:256',
                'site_description' => 'nullable|string|max:1024',
                'default_language' => 'required|string|max:10',
                'registration_is_enabled' => 'boolean',
                'maintenance_is_enabled' => 'boolean',
                'seo_is_enabled' => 'boolean',
                'iframe_is_enabled' => 'boolean',
                'whitelabel_is_enabled' => 'boolean',
            ],
            'payment' => [
                'currency' => 'required|string|max:3',
                'auto_currency_detection' => 'boolean',
                'user_plan_expiry_reminder' => 'boolean',
            ],
            'analytics' => [
                'email_reports_is_enabled' => 'boolean',
                'annotations_is_enabled' => 'boolean',
                'sessions_replays_is_enabled' => 'boolean',
                'websites_heatmaps_is_enabled' => 'boolean',
                'dashboard_views_is_enabled' => 'boolean',
            ],
            'socials' => [
                'google' => 'nullable|array',
                'github' => 'nullable|array',
                'facebook' => 'nullable|array',
            ],
            'webhooks' => [
                'start_url' => 'nullable|url',
                'end_url' => 'nullable|url',
            ],
            default => [],
        };

        // 套餐设置允许任意 JSON 值
        if (str_starts_with($group, 'plan_')) {
            return []; // Plan settings validation is flexible
        }

        return $rules;
    }
}
