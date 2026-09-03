<?php

// Monit 产品标识配置（对应原自研 MVC 的 app/includes/product.php）
return [

    'name' => 'Monit',
    'version' => '1.0.0',
    'description' => '自托管网站流量分析平台（隐私优先）',
    'author' => 'PeaseAPI',
    'url' => 'https://github.com/PeaseAPI/Monit',

    /*
    |--------------------------------------------------------------------------
    | 安装向导
    |--------------------------------------------------------------------------
    | installed.lock 存在 = 已安装（EnsureInstalled 中间件据此放行业务路由、
    | 并让 /install 向导失效）；可用 MONIT_INSTALL_LOCK 覆盖路径（测试隔离用）
    */
    'install_lock' => env('MONIT_INSTALL_LOCK', storage_path('installed.lock')),

    /*
    |--------------------------------------------------------------------------
    | 像素采集
    |--------------------------------------------------------------------------
    */
    'pixel' => [
        // 会话空闲超时（秒）：超过该时间无事件则开启新会话
        'session_timeout' => 1800,
        // M23：pixel_key → Website 查询缓存 TTL（秒）；0 = 关闭缓存
        'website_cache_ttl' => 60,
        // 缓存未命中回源 DB 的每 IP 每分钟上限（防随机 pixel_key 扫描打穿 DB/缓存）；
        // 正常流量几乎全部命中缓存不受影响；0 = 关闭该防护
        'website_miss_rate_limit' => 60,
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
        // 默认支付货币（规格书 §10.4：默认 CNY，可后台改为任意 3 字母代码）
        'default_currency' => 'CNY',

        /*
         |----------------------------------------------------------------------
         | 预设货币表（规格书 §10.4：多货币与汇率）
         |----------------------------------------------------------------------
         | rate 含义：1 默认货币 = rate 该货币（基准恒为 1）。
         | 后台「支付」组可覆盖任意行 / 新增任意货币（存 settings payment.currencies）。
         */
        'currencies' => [
            'CNY' => ['name' => '人民币', 'symbol' => '元', 'rate' => 1],
            'USD' => ['name' => '美元', 'symbol' => '$', 'rate' => 0.14],
            'EUR' => ['name' => '欧元', 'symbol' => '€', 'rate' => 0.13],
            'GBP' => ['name' => '英镑', 'symbol' => '£', 'rate' => 0.11],
            'JPY' => ['name' => '日元', 'symbol' => '¥', 'rate' => 21.5],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI 接入（规格书 §12.6：国内大模型统一接入）
    |--------------------------------------------------------------------------
    | provider 预设：三家国内厂商均提供 OpenAI 兼容 chat/completions 端点，
    | 统一走 Bearer 认证；openai_compatible 可接 DeepSeek/Kimi/GLM 等任意兼容网关。
    | 凭据在管理后台「AI 助手」组配置（settings ai.*），不落 .env。
    */
    'ai' => [
        'default_provider' => 'log',
        'providers' => [
            'aliyun_bailian' => [
                'label' => '阿里百炼（通义千问 DashScope）',
                'base_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1',
                'default_model' => 'qwen-plus',
            ],
            'tencent_hunyuan' => [
                'label' => '腾讯混元',
                'base_url' => 'https://api.hunyuan.cloud.tencent.com/v1',
                'default_model' => 'hunyuan-turbos-latest',
            ],
            'volcengine_ark' => [
                'label' => '火山方舟（豆包）',
                'base_url' => 'https://ark.cn-beijing.volces.com/api/v3',
                'default_model' => 'doubao-1-5-pro-32k-250115',
            ],
            'openai_compatible' => [
                'label' => '自定义 OpenAI 兼容端点（DeepSeek/Kimi/GLM 等）',
                'base_url' => '',
                'default_model' => '',
            ],
            'log' => [
                'label' => 'log（开发调试，不发真实请求）',
                'base_url' => '',
                'default_model' => 'debug',
            ],
        ],
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
    | 前台可用语言（原版 settings.languages 对应物）
    |--------------------------------------------------------------------------
    | lang/{code}.json 必须存在；/locale/{code} 切换 session，SetLocale 中间件生效。
    | 新增语言：放好 lang 文件后在此登记即可。
    */
    'locales' => [
        'zh_CN' => ['label' => '简体中文', 'flag' => '🇨🇳'],
        'zh_TW' => ['label' => '繁體中文', 'flag' => '🇹🇼'],
        'en' => ['label' => 'English', 'flag' => '🇺🇸'],
        'ru' => ['label' => 'Русский', 'flag' => '🇷🇺'],
        'be' => ['label' => 'Беларуская', 'flag' => '🇧🇾'],
        'ms' => ['label' => 'Bahasa Melayu', 'flag' => '🇲🇾'],
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
