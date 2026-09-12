<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * settings 死键清理（二十轮：动态读法全扫 + 死配置组终判）：
 *
 * 1. theme 组整组（theme.theme / theme.primary_color / theme.secondary_color /
 *    theme.white_label_is_enabled）——全仓零运行时消费。功能已被取代：
 *    主题解析 = Brand::landingTheme()（branding.landing_theme，M23），
 *    前台风格 = main.default_theme_style / theme_style_change_is_enabled，
 *    品牌色 = branding.primary_color，白标 = plan_features 用户套餐层
 *    （white_labeling_is_enabled）。后台「主题设置」tab 一并移除。
 *
 * 2. ads.ad_blocker_detector_* 三键——广告拦截检测功能未实现，零运行时消费
 *    （ads 组主体 ads_is_enabled/ads_header/ads_footer 为活键保留）。
 */
return new class extends Migration
{
    /**
     * 执行迁移：死组/死键残值清理（防御性，线上实测均无键）
     */
    public function up(): void
    {
        $deadKeys = [
            'theme.theme',
            'theme.primary_color',
            'theme.secondary_color',
            'theme.white_label_is_enabled',
            'ads.ad_blocker_detector_is_enabled',
            'ads.ad_blocker_detector_lock_is_enabled',
            'ads.ad_blocker_detector_delay',
        ];

        foreach ($deadKeys as $key) {
            DB::table('settings')->where('key', $key)->delete();
        }
    }

    /**
     * 回滚：空操作
     *
     * 零运行时消费的死键，删除无数据丢失风险。
     */
    public function down(): void
    {
        //
    }
};
