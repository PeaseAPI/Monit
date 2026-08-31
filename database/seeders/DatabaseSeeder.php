<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Monit 初始数据总入口（CLI `php artisan db:seed`）
 * 仅写入核心初始数据（CoreDataSeeder：free/pro 套餐 + 平台默认设置）——生产安全，无演示账号。
 *
 * 演示数据（admin@monit.dev 等演示账户）不随默认 seed 执行，需要时手动运行：
 *   php artisan db:seed --class=DemoDataSeeder --force
 *
 * 网页安装向导（InstallController）同样只跑 CoreDataSeeder。
 *
 * 生产配置导入（www_monit_cn.sql 提取的三档定价/税费/品牌备案）不随默认 seed 执行：
 *   php artisan db:seed --class=ProductionSeeder --force
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CoreDataSeeder::class);
    }
}
