<?php

/**
 * 插件：📸 Image Optimizer（规格书 §14.3 #5、§14.9）
 * 自动压缩上传图片（JPG/PNG/GIF，GD 引擎），质量可配，保留原图可选。
 * 上传拦截：helper monit_image_optimize($path) 可在任意上传管线调用；
 * 另提供 Admin 批量优化端点与统计。
 */

return [
    'id' => 'image-optimizer',
    'title' => '📸 Image Optimizer',
    'description' => '图片自动压缩（PHP GD）：上传拦截 helper monit_image_optimize() + Admin 批量优化 uploads/ 目录 + 压缩统计。质量与保留原图可配置。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'quality' => [
            'type' => 'text',
            'label' => 'JPEG 压缩质量（0-100）',
            'default' => '82',
        ],
        'keep_original' => [
            'type' => 'select',
            'label' => '保留原图备份（.original 后缀）',
            'default' => '0',
            'options' => ['1' => '保留', '0' => '不保留'],
        ],
    ],
];
