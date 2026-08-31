<?php

/**
 * PWA 卸载钩子：清理上传图标目录（规格书 §14.6 uploads/pwa/）
 */
$iconDir = public_path('uploads/pwa');

if (is_dir($iconDir)) {
    foreach (glob($iconDir.'/*.png') ?: [] as $file) {
        @unlink($file);
    }

    @rmdir($iconDir);
}
