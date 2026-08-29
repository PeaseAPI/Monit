<?php

/**
 * 插件：🖼️ Dynamic OG Images（规格书 §14.3 #7，免费）
 * 动态生成社交分享 OG 图。
 */

return [
    'id' => 'dynamic-og-images',
    'title' => '🖼️ Dynamic OG Images',
    'description' => '动态生成 1200x630 社交分享图：/og-image?title=...&description=...（GD 渲染 PNG），可用于博客文章与落地页 meta og:image。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'is_enabled' => [
            'type' => 'bool',
            'label' => '启用动态 OG 图',
            'default' => true,
        ],
        'background' => [
            'type' => 'text',
            'label' => '背景色（hex，不含 #）',
            'default' => '0f172a',
        ],
        'foreground' => [
            'type' => 'text',
            'label' => '文字色（hex，不含 #）',
            'default' => 'ffffff',
        ],
        'brand_text' => [
            'type' => 'text',
            'label' => '水印文字',
            'default' => 'Monit',
        ],
    ],
];
