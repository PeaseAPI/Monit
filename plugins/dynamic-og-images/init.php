<?php

/**
 * Dynamic OG Images 启动入口：注册 /og-image 路由（GD 生成 1200x630 PNG）
 */

use App\Support\PluginManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/og-image', function (Request $request) {
    if (! PluginManager::isActive('dynamic-og-images')
        || ! PluginManager::setting('dynamic-og-images', 'is_enabled', true)) {
        abort(404);
    }

    $title = mb_substr($request->query('title', config('app.name', 'Monit')), 0, 80);
    $description = mb_substr($request->query('description', ''), 0, 120);

    $bg = PluginManager::setting('dynamic-og-images', 'background', '0f172a');
    $fg = PluginManager::setting('dynamic-og-images', 'foreground', 'ffffff');
    $brand = (string) PluginManager::setting('dynamic-og-images', 'brand_text', 'Monit');

    $width = 1200;
    $height = 630;

    $image = imagecreatetruecolor($width, $height);

    // 背景渐变（深色底 + 品牌色对角渐变）
    [$br, $bgc, $bb] = [hexdec(substr($bg, 0, 2)), hexdec(substr($bg, 2, 2)), hexdec(substr($bg, 4, 2))];
    imagefill($image, 0, 0, imagecolorallocate($image, $br, $bgc, $bb));

    for ($x = 0; $x < $width; $x += 4) {
        $t = $x / $width;
        $color = imagecolorallocate(
            $image,
            (int) ($br + (99 - $br) * $t),
            (int) ($bgc + (102 - $bgc) * $t),
            (int) ($bb + (241 - $bb) * $t),
        );
        imageline($image, $x, 0, $x, $height, $color);
    }

    $white = imagecolorallocate($image, hexdec(substr($fg, 0, 2)), hexdec(substr($fg, 2, 2)), hexdec(substr($fg, 4, 2)));
    $gray = imagecolorallocate($image, 148, 163, 184);

    // 标题（自动换行）
    $font = 5; // 内置大字体
    $lineHeight = 42;
    $y = 220;
    foreach (mb_str_split($title, 24) ?: [$title] as $line) {
        imagestring($image, $font, 80, (int) $y, $line, $white);
        $y += $lineHeight;
    }

    // 描述
    if ($description !== '') {
        $y += 10;
        foreach (mb_str_split($description, 60) ?: [] as $line) {
            imagestring($image, 4, 80, (int) $y, $line, $gray);
            $y += 28;
        }
    }

    // 水印
    imagestring($image, 4, 80, $height - 60, $brand, $gray);

    return response()->stream(function () use ($image) {
        imagepng($image);
        imagedestroy($image);
    }, 200, ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=3600']);
})->name('og-image');
