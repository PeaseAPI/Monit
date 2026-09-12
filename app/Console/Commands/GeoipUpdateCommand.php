<?php

namespace App\Console\Commands;

use App\Support\Typed;
use Illuminate\Console\Command;

/**
 * GeoIP 数据库更新命令
 * 下载 db-ip.com 免费城市库到 storage/app/geoip/country.mmdb，
 * 以及 ip2region 中文省/市库到 storage/app/geoip/ip2region.xdb
 * 用法：php artisan geoip:update
 *
 * M30：country lite 无 city/subdivisions 字段（线上曾因用 country 库导致
 * 「只显示国家、无省市」），改为下载 city lite（~60MB，含省/州与城市英文名），
 * 路径保持 services.geoip.mmdb_path 不变，country 用途（国家/大洲）完全兼容。
 * M31：新增 ip2region_v4.xdb 下载（~11MB，中国 IP 中文省/市，db-ip 免费库无
 * 中文名）；db-ip 当月文件未发布（404）时自动回退上月重试。
 */
class GeoipUpdateCommand extends Command
{
    /** @var list<string> ip2region xdb 下载源（jsdelivr 国内稳定，raw 为备源） */
    protected const IP2REGION_XDB_URLS = [
        'https://cdn.jsdelivr.net/gh/lionsoul2014/ip2region@master/data/ip2region_v4.xdb',
        'https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region_v4.xdb',
    ];

    protected $signature = 'geoip:update {--force : 强制重新下载}';

    protected $description = '下载/更新 GeoIP 数据库（db-ip city lite + ip2region 中文省市库）';

    public function handle(): int
    {
        $mmdbOk = $this->updateMmdb($this->option('force'));
        $xdbOk = $this->updateIp2RegionXdb($this->option('force'));

        return ($mmdbOk && $xdbOk) ? self::SUCCESS : self::FAILURE;
    }

    /**
     * 下载 db-ip city lite（当月 404 未发布时自动回退上月）
     */
    protected function updateMmdb(bool $force): bool
    {
        $path = config('services.geoip.mmdb_path', storage_path('app/geoip/country.mmdb'));

        // 确保路径非空且为绝对路径
        if (($path ?? '') === '') {
            $path = storage_path('app/geoip/country.mmdb');
        }

        $path = Typed::string($path);
        $dir = dirname($path);

        if ($dir === '.' || $dir === '/') {
            $this->error("解析出的目录路径无效：{$dir}，请检查 GEOIP_MMDB_PATH 环境变量或 storage_path() 配置。");

            return false;
        }

        if (! is_dir($dir)) {
            if (! mkdir($dir, 0755, true)) {
                $this->error("无法创建目录：{$dir}，请检查 storage 目录权限。");

                return false;
            }
            $this->info("已创建目录：{$dir}");
        }

        // 本月文件已存在且非强制 → 跳过
        if (! $force && is_file($path) && filemtime($path) >= strtotime('first day of this month 00:00:00')) {
            $this->info('GeoIP 城市库已是本月版本，跳过下载。使用 --force 强制更新。');

            return true;
        }

        // M30：必须 city 库——country 库无 city/subdivisions 字段，省份/城市维度将永远为空；
        // M31：当月文件未发布（每月初 db-ip 才更新，404）时回退上月
        $urls = [
            'https://download.db-ip.com/free/dbip-city-lite-'.date('Y-m').'.mmdb.gz',
            'https://download.db-ip.com/free/dbip-city-lite-'.date('Y-m', strtotime('-1 month')).'.mmdb.gz',
        ];

        $lastFailure = '';

        foreach ($urls as $index => $url) {
            $label = ($index === 0) ? '当月' : '上月';
            $this->info("正在下载（{$label}城市库，含省份/城市）：{$url}");

            $gzData = @file_get_contents($url);

            if ($gzData === false) {
                $lastFailure = "下载失败（{$label}）";

                if ($index === 0) {
                    $this->warn('当月城市库尚未发布（404），回退上月版本…');
                }

                continue;
            }

            $data = @gzdecode($gzData);

            if ($data === false) {
                $lastFailure = '解压失败，下载的文件可能已损坏';

                continue;
            }

            if (file_put_contents($path, $data) === false) {
                $this->error("写入失败：{$path}");

                return false;
            }

            $size = number_format(strlen($data) / 1024, 1);
            $this->info("✓ GeoIP 城市库已更新：{$path} ({$size} KB)".($index > 0 ? '（上月版本，当月发布后可重跑更新）' : ''));

            return true;
        }

        $this->error("GeoIP 城市库更新失败：{$lastFailure}。");

        return false;
    }

    /**
     * 下载 ip2region 中文省/市库（M31，中国 IP 中文地理）
     */
    protected function updateIp2RegionXdb(bool $force): bool
    {
        $path = Typed::string(config('services.geoip.ip2region_path'));

        if ($path === '') {
            $path = storage_path('app/geoip/ip2region.xdb');
        }

        $dir = dirname($path);

        if ($dir !== '.' && $dir !== '/' && ! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            $this->error("无法创建目录：{$dir}，请检查 storage 目录权限。");

            return false;
        }

        // 本月文件已存在且非强制 → 跳过
        if (! $force && is_file($path) && filemtime($path) >= strtotime('first day of this month 00:00:00')) {
            $this->info('ip2region 中文省市库已是本月版本，跳过下载。');

            return true;
        }

        foreach (self::IP2REGION_XDB_URLS as $index => $url) {
            $this->info('正在下载 ip2region 中文省市库：'.$url);

            $data = @file_get_contents($url);

            // 完整库约 11MB，以 1MB 下限拦截 CDN 错误页/半截响应
            if ($data !== false && strlen($data) > 1024 * 1024) {
                if (file_put_contents($path, $data) === false) {
                    $this->error("写入失败：{$path}");

                    return false;
                }

                $size = number_format(strlen($data) / 1024 / 1024, 1);
                $this->info("✓ ip2region 中文省市库已更新：{$path} ({$size} MB)");

                return true;
            }

            if ($index === 0) {
                $this->warn('该源下载失败，尝试备用源…');
            }
        }

        $this->error('ip2region 中文省市库下载失败（所有镜像源均不可用）。中国访客省市将暂时回退 db-ip 英文名，不影响其余功能。');

        return false;
    }
}
