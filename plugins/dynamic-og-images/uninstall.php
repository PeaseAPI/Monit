<?php

/**
 * Dynamic OG Images 卸载钩子：清理 OG 图缓存目录
 */

$cacheDir = storage_path('app/og-images');

if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '/*.png') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($cacheDir);
}
