<?php

// Monit 产品标识配置（替代原 66Analytics 的 app/includes/product.php）
return [

    'name' => 'Monit',
    'version' => '1.0.0',
    'description' => '自托管网站流量分析平台（隐私优先）',
    'author' => 'PeaseAPI',
    'url' => 'https://github.com/PeaseAPI/Monit',

    /*
    |--------------------------------------------------------------------------
    | 像素采集
    |--------------------------------------------------------------------------
    */
    'pixel' => [
        // 会话空闲超时（秒）：超过该时间无事件则开启新会话
        'session_timeout' => 1800,
        // 单页最大自定义参数键值对
        'max_custom_parameters' => 10,
        // 事件过期天数（数据保留）
        'events_retention_days' => 365,
        // 回放数据保留天数
        'replays_retention_days' => 30,
        // 每次上报载荷最大字节数
        'max_payload_size' => 65536,
    ],

    /*
    |--------------------------------------------------------------------------
    | 默认套餐配额（free 套餐 seed 数据）
    |--------------------------------------------------------------------------
    */
        'plan_defaults' => [
        'websites_limit' => 3,
        'sessions_events_limit' => 10000,
        'events_children_limit' => 5000,
        'sessions_replays_limit' => 500,
        'websites_heatmaps_limit' => 3,
        'websites_goals_limit' => 10,
        'annotations_limit' => 10,
        'domains_limit' => 1,
        'teams_is_enabled' => false,
        'websites_sessions_replays_is_enabled' => false,
        'websites_heatmaps_is_enabled' => false,
        'websites_goals_is_enabled' => true,
        'websites_public_statistics_is_enabled' => false,
        'websites_excluded_ips_is_enabled' => true,
        'websites_timezones_is_enabled' => true,
        'websites_email_reports_is_enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | 支付配置（规格书 §11）
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'offline_instructions' => '请将款项汇至以下账户，并在付款后上传凭证：<br>银行：XXX<br>账号：XXX<br>户名：XXX',
        'supported_processors' => [
            'stripe', 'paypal', 'razorpay', 'mollie', 'paystack', 'offline',
            'wechat', 'alipay',
            'payu', 'iyzico', 'yookassa', 'cryptocom', 'paddle', 'mercadopago',
            'midtrans', 'flutterwave', 'lemonsqueezy', 'myfatoorah', 'klarna',
            'plisio', 'revolut', 'onepay',
        ],
        'default_currency' => 'USD',
    ],

    /*
    |--------------------------------------------------------------------------
    | 套餐可售功能项（规格书 §10.2 available_plan_features.php）
    |--------------------------------------------------------------------------
    | type: bool 开关 / int 数值配额；default 仅用于表单初始值。
    | 套餐编辑器按此矩阵渲染，结果存入 plans.settings JSON。
    */
    'plan_features' => [
        'websites_limit' => ['label' => '网站数量上限', 'type' => 'int', 'default' => 10],
        'sessions_events_limit' => ['label' => '会话事件配额', 'type' => 'int', 'default' => 100000],
        'sessions_events_retention' => ['label' => '会话事件留存（天）', 'type' => 'int', 'default' => 90],
        'events_children_limit' => ['label' => '事件子项配额', 'type' => 'int', 'default' => 250000],
        'events_children_retention' => ['label' => '事件子项留存（天）', 'type' => 'int', 'default' => 90],
        'websites_goals_limit' => ['label' => '每站目标数上限', 'type' => 'int', 'default' => 10],
        'annotations_limit' => ['label' => '标注数上限', 'type' => 'int', 'default' => 100],
        'dashboard_views_limit' => ['label' => '仪表盘视图上限', 'type' => 'int', 'default' => 10],
        'sessions_replays_limit' => ['label' => '回放配额', 'type' => 'int', 'default' => 1000],
        'sessions_replays_retention' => ['label' => '回放留存（天）', 'type' => 'int', 'default' => 30],
        'websites_heatmaps_limit' => ['label' => '热图数上限', 'type' => 'int', 'default' => 10],
        'domains_limit' => ['label' => '自定义域名上限', 'type' => 'int', 'default' => 3],
        'additional_domains' => ['label' => '额外域名数', 'type' => 'int', 'default' => 0],
        'affiliate_commission_percentage' => ['label' => '联盟佣金比例（%，插件启用时）', 'type' => 'int', 'default' => 20],
        'email_reports_is_enabled' => ['label' => '邮件报表', 'type' => 'bool', 'default' => false],
        'teams_is_enabled' => ['label' => '团队功能', 'type' => 'bool', 'default' => false],
        'no_ads' => ['label' => '去广告', 'type' => 'bool', 'default' => false],
        'api_is_enabled' => ['label' => 'API 权限', 'type' => 'bool', 'default' => true],
        'white_labeling_is_enabled' => ['label' => '白标', 'type' => 'bool', 'default' => false],
        'export' => ['label' => '数据导出', 'type' => 'bool', 'default' => true],
        'push_notifications_is_enabled' => ['label' => 'Push 通知（插件）', 'type' => 'bool', 'default' => false],
        'push_notifications_subscribers_limit' => ['label' => 'Push 订阅者限制', 'type' => 'int', 'default' => 1000],
        'push_notifications_campaigns_limit' => ['label' => 'Push Campaign 限制', 'type' => 'int', 'default' => 10],
        'pwa_is_enabled' => ['label' => 'PWA（插件）', 'type' => 'bool', 'default' => false],
        'offload_is_enabled' => ['label' => 'Offload（插件）', 'type' => 'bool', 'default' => false],
        'image_optimizer_is_enabled' => ['label' => '图片优化（插件）', 'type' => 'bool', 'default' => false],
        'email_shield_is_enabled' => ['label' => '邮件防爬（插件）', 'type' => 'bool', 'default' => true],
        'dynamic_og_images_is_enabled' => ['label' => '动态 OG 图片（插件）', 'type' => 'bool', 'default' => true],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ed25519 离线 License（规格书 §15.2）
    |--------------------------------------------------------------------------
    | path: license.json 路径（默认 storage/app/license.json）
    | public_key: 内置公钥（hex）；签发工具 monit:license-generate 生成密钥对后填入
    */
    'license' => [
        'path' => storage_path('app/license.json'),
        'public_key' => env('MONIT_LICENSE_PUBLIC_KEY', ''),
    ],
];
