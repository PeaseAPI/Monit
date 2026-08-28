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
        'teams_is_enabled' => false,
        'websites_sessions_replays_is_enabled' => false,
        'websites_heatmaps_is_enabled' => false,
        'websites_goals_is_enabled' => true,
        'websites_public_statistics_is_enabled' => false,
        'websites_excluded_ips_is_enabled' => true,
        'websites_timezones_is_enabled' => true,
        'websites_email_reports_is_enabled' => false,
    ],
];
