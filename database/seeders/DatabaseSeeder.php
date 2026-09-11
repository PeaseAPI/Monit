<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Monit 初始数据总入口（CLI `php artisan db:seed`）
 * 写入核心初始数据（CoreDataSeeder：free/pro 套餐 + 平台默认设置）
 * 与帮助中心官方文档（HelpCenterSeeder：10 分类 / 45 篇使用与设置文档）——生产安全，无演示账号。
 *
 * 演示数据（admin@monit.dev 等演示账户）不随默认 seed 执行，需要时手动运行：
 *   php artisan db:seed --class=DemoDataSeeder --force
 *
 * 热图/回放演示数据（不创建用户，仅填充热图和会话回放记录）：
 *   php artisan db:seed --class=DemoHeatmapReplaySeeder --force
 * （安装向导已自动执行此 seeder）
 *
 * 网页安装向导（InstallController）同样只跑 CoreDataSeeder + DemoHeatmapReplaySeeder。
 *
 * 生产配置导入（www_monit_cn.sql 提取的三档定价/税费/品牌备案）不随默认 seed 执行：
 *   php artisan db:seed --class=ProductionSeeder --force
 *
 * 帮助中心文档单独重灌（幂等，按 url 覆盖更新）：
 *   php artisan db:seed --class=HelpCenterSeeder --force
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreDataSeeder::class,
            HelpCenterSeeder::class,
        ]);
    }
}
