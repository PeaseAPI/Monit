<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * GeoIP 数据库更新命令
 * 下载 db-ip.com 免费国家库到 storage/app/geoip/country.mmdb
 * 用法：php artisan geoip:update
 */
class GeoipUpdateCommand extends Command
{
    protected $signature = 'geoip:update {--force : 强制重新下载}';

    protected $description = '下载/更新 GeoIP 国家数据库（db-ip.com 免费 country lite）';

    public function handle(): int
    {
        $path = config('services.geoip.mmdb_path', storage_path('app/geoip/country.mmdb'));
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
            $this->info("已创建目录：{$dir}");
        }

        // 本月文件已存在且非强制 → 跳过
        if (! $this->option('force') && is_file($path) && filemtime($path) >= strtotime('first day of this month 00:00:00')) {
            $this->info('GeoIP 数据库已是本月版本，跳过下载。使用 --force 强制更新。');

            return self::SUCCESS;
        }

        $url = 'https://download.db-ip.com/free/dbip-country-lite-'.date('Y-m').'.mmdb.gz';
        $this->info("正在下载：{$url}");

        $gzData = @file_get_contents($url);
        if ($gzData === false) {
            $this->error('下载失败，请检查网络连接。');

            return self::FAILURE;
        }

        $data = @gzdecode($gzData);
        if ($data === false) {
            $this->error('解压失败，下载的文件可能已损坏。');

            return self::FAILURE;
        }

        if (file_put_contents($path, $data) === false) {
            $this->error("写入失败：{$path}");

            return self::FAILURE;
        }

        $size = number_format(strlen($data) / 1024, 1);
        $this->info("✓ GeoIP 数据库已更新：{$path} ({$size} KB)");

        return self::SUCCESS;
    }
}
