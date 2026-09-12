<?php

namespace App\Services;

use App\Models\Heatmap;
use App\Support\Typed;

/**
 * 热图服务端标准视口截图（M30）
 *
 * 背景：热图底图原本只依赖访客端 rrweb 快照——desktop 快照容易获得，
 * 但 Tablet/Mobile 快照必须等真实设备访客到站才产生，且宽度随设备五花八门，
 * 造成「📱 Tablet / 📲 Mobile 标签页无底图或底图不符合标准视口」。
 *
 * 本服务用服务器端 chrome-headless-shell 按标准设备视口直接截图目标页面，
 * 生成的图片存放在 public/uploads/heatmap-shots/{website_id}/ 下（HTTP 直接可达）：
 *   - desktop: 1366×900（长页捕获高 2600）
 *   - tablet : 768×1024（长页捕获高 3600，UA=iPad）
 *   - mobile : 375×812（长页捕获高 4800，UA=iPhone）
 * 截图后用 GD 从底部裁掉纯色空白，得到近似全页底图，供热图详情页
 * 作为三个设备标签页的底图（点击/滚动热力 canvas 叠加其上）。
 *
 * 依赖：chrome-headless-shell 二进制（config services.heatmap.chrome_bin）+ PHP GD。
 * 未安装二进制时 capture* 静默返回 null（回退 rrweb 快照展示），不影响其他功能。
 */
class HeatmapScreenshot
{
    /** 标准设备视口（宽, 高） */
    public const VIEWPORTS = [
        'desktop' => [1366, 900],
        'tablet' => [768, 1024],
        'mobile' => [375, 812],
    ];

    /** 长页捕获窗口高度（GD 裁剪底部空白后得近似全页） */
    public const CAPTURE_HEIGHTS = [
        'desktop' => 2600,
        'tablet' => 3600,
        'mobile' => 4800,
    ];

    /** 各设备 UA（让响应式站点按设备渲染移动/桌面布局） */
    public const USER_AGENTS = [
        'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'tablet' => 'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
    ];

    /**
     * 指定设备的截图是否已存在
     */
    public function exists(Heatmap $heatmap, string $device): bool
    {
        return is_file($this->absolutePath($heatmap, $device));
    }

    /**
     * 截图的公开访问 URL（未生成返回 null）
     */
    public function url(Heatmap $heatmap, string $device): ?string
    {
        if (! $this->exists($heatmap, $device)) {
            return null;
        }

        return asset('uploads/heatmap-shots/'.$heatmap->website_id.'/'.$this->fileName($heatmap, $device));
    }

    /**
     * 截图一张（指定设备）；返回公开 URL，失败返回 null
     */
    public function capture(Heatmap $heatmap, string $device): ?string
    {
        if (! isset(static::VIEWPORTS[$device])) {
            return null;
        }

        $bin = $this->chromeBin();
        if ($bin === '') {
            return null;
        }

        [$width] = static::VIEWPORTS[$device];
        $height = static::CAPTURE_HEIGHTS[$device];

        $dir = dirname($this->absolutePath($heatmap, $device));
        if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
            return null;
        }

        $tmpPng = tempnam(sys_get_temp_dir(), 'hmshot').'.png';
        $url = $heatmap->website->scheme.'://'.$heatmap->website->host.$heatmap->path;
        $timeout = Typed::int(config('services.heatmap.timeout') ?? 90);

        $command = sprintf(
            'timeout %d %s --headless --no-sandbox --disable-gpu --disable-dev-shm-usage'.
            ' --hide-scrollbars --force-device-scale-factor=1 --user-agent=%s --window-size=%d,%d'.
            ' --virtual-time-budget=12000 --screenshot=%s %s 2>&1',
            $timeout,
            escapeshellarg($bin),
            escapeshellarg(static::USER_AGENTS[$device]),
            $width,
            $height,
            escapeshellarg($tmpPng),
            escapeshellarg($url),
        );

        $output = @shell_exec($command);

        if (! is_file($tmpPng) || filesize($tmpPng) === 0) {
            is_string($output) && report(new \RuntimeException('heatmap screenshot failed: '.mb_substr($output, 0, 300)));

            return null;
        }

        // GD 裁掉底部纯色空白（近似全页）后写 JPEG（体积小、加载快）
        $cropped = $this->cropTrailingBlank($tmpPng, $dir.'/'.$this->fileName($heatmap, $device));
        @unlink($tmpPng);

        if (! $cropped) {
            return null;
        }

        return $this->url($heatmap, $device);
    }

    /**
     * 三端全部截图（桌面/平板/手机标准视口）
     *
     * @return array<string, ?string> device => url|null
     */
    public function captureAll(Heatmap $heatmap): array
    {
        $result = [];

        foreach (array_keys(static::VIEWPORTS) as $device) {
            $result[$device] = $this->capture($heatmap, $device);
        }

        return $result;
    }

    /**
     * GD 裁剪底部空白并写 JPEG；成功返回 true
     */
    protected function cropTrailingBlank(string $sourcePng, string $targetJpg): bool
    {
        $image = @imagecreatefrompng($sourcePng);
        if ($image === false) {
            return false;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $keep = 60; // 内容底部保留的空白 padding

        $lastContentRow = $height - 1;
        for ($y = $height - 1; $y >= 0; $y--) {
            if (! $this->isBlankRow($image, $width, $y)) {
                $lastContentRow = $y;
                break;
            }
        }

        $cropHeight = min($height, $lastContentRow + 1 + $keep);
        if ($cropHeight < 200) {
            $cropHeight = min($height, 200); // 防极端情况裁成 0
        }

        $canvas = imagecreatetruecolor($width, $cropHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $cropHeight);

        $ok = imagejpeg($canvas, $targetJpg, 82);
        imagedestroy($canvas);
        imagedestroy($image);

        return (bool) $ok;
    }

    /**
     * 一行是否纯白（按 8px 步进采样，兼顾速度与准确度）
     */
    protected function isBlankRow(\GdImage $image, int $width, int $y): bool
    {
        for ($x = 0; $x < $width; $x += 8) {
            $rgb = imagecolorat($image, $x, $y);
            if (($rgb >> 16 & 0xFF) < 245 || ($rgb >> 8 & 0xFF) < 245 || ($rgb & 0xFF) < 245) {
                return false;
            }
        }

        return true;
    }

    protected function fileName(Heatmap $heatmap, string $device): string
    {
        return 'heatmap_'.$heatmap->heatmap_id.'_'.$device.'.jpg';
    }

    protected function absolutePath(Heatmap $heatmap, string $device): string
    {
        return public_path('uploads/heatmap-shots/'.$heatmap->website_id.'/'.$this->fileName($heatmap, $device));
    }

    /**
     * chrome-headless-shell 二进制路径；未配置/不存在返回空串（功能停用）
     */
    protected function chromeBin(): string
    {
        $bin = Typed::string(config('services.heatmap.chrome_bin') ?? '');

        if ($bin === '' || ! is_file($bin) || ! is_executable($bin)) {
            return '';
        }

        return $bin;
    }
}
