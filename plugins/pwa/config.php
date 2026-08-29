<?php

/**
 * 插件：🚀 PWA（规格书 §14.3 #2）
 * Web App Manifest + Service Worker，支持安装到主屏。
 */

return [
    'id' => 'pwa',
    'title' => '🚀 PWA',
    'description' => '渐进式 Web 应用：动态 manifest.json 端点 + Service Worker（CacheFirst 静态资源 / NetworkFirst 页面），可将站点安装到主屏。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'name' => ['type' => 'text', 'label' => '应用名称', 'default' => 'Monit Analytics'],
        'short_name' => ['type' => 'text', 'label' => '短名称', 'default' => 'Monit'],
        'description' => ['type' => 'text', 'label' => '应用描述', 'default' => 'Privacy-first web analytics'],
        'theme_color' => ['type' => 'text', 'label' => '主题色（hex，含 #）', 'default' => '#4f46e5'],
        'background_color' => ['type' => 'text', 'label' => '背景色（hex，含 #）', 'default' => '#0f172a'],
    ],
];
