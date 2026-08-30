<?php

/**
 * 插件：🔔 Push Notifications（规格书 §14.3 #1、§14.5）
 * Web Push 订阅者收集 + Campaign 定向/批量推送（RFC 8291/8292）。
 * VAPID 密钥对由 Admin 生成后填入设置；发送引擎见 App\Services\WebPushService。
 */

return [
    'id' => 'push-notifications',
    'title' => '🔔 Push Notifications',
    'description' => 'Web Push 推送：访客浏览器订阅 + 管理端 Campaign 定向发送 + 批量推送（Cron 分批）。基于 VAPID（RFC 8292）与 aes128gcm 载荷加密（RFC 8291）。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'vapid_public_key' => [
            'type' => 'text',
            'label' => 'VAPID 公钥（base64url，65 字节 raw）',
            'default' => '',
        ],
        'vapid_private_key' => [
            'type' => 'text',
            'label' => 'VAPID 私钥（base64url，32 字节 raw）',
            'default' => '',
        ],
        'subject' => [
            'type' => 'text',
            'label' => 'VAPID 联系方式（mailto:）',
            'default' => 'mailto:admin@example.com',
        ],
        'batch_size' => [
            'type' => 'text',
            'label' => 'Cron 每批发送数量',
            'default' => '100',
        ],
    ],
];
