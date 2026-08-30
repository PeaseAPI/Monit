<?php

/**
 * 插件：💻 Offload（规格书 §14.3 #4、§14.8）
 * S3 兼容外部存储：会话回放 Offload + 静态资源 CDN 前缀。
 * 引擎：App\Support\S3Client（纯 PHP SigV4，支持 AWS S3 / MinIO / 兼容服务）。
 */

return [
    'id' => 'offload',
    'title' => '💻 Offload',
    'description' => '外部存储与 CDN：24h 前会话回放序列化上传 S3（Cron 分批），静态资源 URL 可替换为 CDN 前缀（Blade @offloadAsset 指令）。',
    'version' => '1.0.0',
    'author' => 'Monit',
    'url' => 'https://monit.dev',

    'settings' => [
        'storage_driver' => [
            'type' => 'select',
            'label' => '存储驱动（后台「存储卸载」设置组优先）',
            'default' => 's3',
            'options' => [
                's3' => 'AWS S3',
                'minio' => 'MinIO / S3 兼容',
                'aliyun_oss' => '阿里云 OSS',
                'tencent_cos' => '腾讯云 COS',
            ],
        ],
        's3_access_key' => [
            'type' => 'text',
            'label' => 'S3 Access Key',
            'default' => '',
        ],
        's3_secret_key' => [
            'type' => 'text',
            'label' => 'S3 Secret Key',
            'default' => '',
        ],
        's3_bucket' => [
            'type' => 'text',
            'label' => 'S3 Bucket',
            'default' => 'monit-replays',
        ],
        's3_region' => [
            'type' => 'text',
            'label' => 'S3 Region',
            'default' => 'us-east-1',
        ],
        's3_endpoint' => [
            'type' => 'text',
            'label' => '自定义端点（MinIO 等，留空用 AWS）',
            'default' => '',
        ],
        'cdn_url' => [
            'type' => 'text',
            'label' => '静态资源 CDN 前缀（如 https://cdn.example.com，留空禁用）',
            'default' => '',
        ],
        'batch_size' => [
            'type' => 'text',
            'label' => 'Cron 每批处理回放数',
            'default' => '25',
        ],
        'delete_after_upload' => [
            'type' => 'select',
            'label' => '上传后删除本地回放事件',
            'default' => '1',
            'options' => ['1' => '删除（节省空间）', '0' => '保留'],
        ],
    ],
];
