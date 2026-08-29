<?php

/**
 * 插件：🛡️ Email Shield（规格书 §14.3 #6，免费）
 * 邮箱混淆输出，防止爬虫收集页面上的明文邮箱。
 */

return [
    'id' => 'email-shield',
    'title' => '🛡️ Email Shield',
    'description' => '邮箱混淆输出：模板中使用 @emailShield 指令或 email_shield() 函数，将邮箱转为 JS 反混淆输出，防止爬虫收集。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'is_enabled' => [
            'type' => 'bool',
            'label' => '启用邮箱混淆',
            'default' => true,
        ],
        'method' => [
            'type' => 'text',
            'label' => '混淆方式（rot13 / entity）',
            'default' => 'rot13',
        ],
    ],
];
