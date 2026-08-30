<?php

/**
 * Image Optimizer 卸载钩子：删除统计表 + 清理 .original 备份文件
 */

use Illuminate\Support\Facades\Schema;

Schema::dropIfExists('image_optimizer_stats');

foreach (glob(public_path('uploads/*.original')) ?: [] as $backup) {
    @unlink($backup);
}
