<?php

namespace App\Support;

/**
 * 对象存储工厂（M17 规格书 §14.8 扩展）
 *
 * 支持 5 种驱动，统一 put/get/delete/exists 接口：
 *   - s3 / minio / custom：App\Support\S3Client（AWS SigV4，S3 兼容）
 *   - aliyun_oss：App\Support\AliyunOssClient（阿里云 OSS）
 *   - tencent_cos：App\Support\TencentCosClient（腾讯云 COS）
 *
 * 配置来源：管理后台「存储卸载」设置组（settings offload.*）优先，
 * 未配置时回落 Offload 插件设置（PluginManager）与 config/services.php（env）。
 */
class ObjectStorage
{
    public const DRIVERS = ['s3', 'minio', 'custom', 'aliyun_oss', 'tencent_cos'];

    /** 当前生效的存储驱动 */
    public static function driver(): string
    {
        $driver = (string) Settings::get('offload.offload_storage_driver', '');

        if ($driver === '') {
            $driver = (string) PluginManager::setting('offload', 'storage_driver', 's3');
        }

        $driver = str_replace('-', '_', $driver);

        return in_array($driver, static::DRIVERS, true) ? $driver : 's3';
    }

    /** 按驱动构建客户端实例 */
    public static function make(): S3Client|AliyunOssClient|TencentCosClient
    {
        return match (static::driver()) {
            'aliyun_oss' => new AliyunOssClient(
                (string) (Settings::get('offload.offload_oss_access_key_id') ?: config('services.oss.access_key_id', '')),
                (string) (Settings::get('offload.offload_oss_access_key_secret') ?: config('services.oss.access_key_secret', '')),
                (string) (Settings::get('offload.offload_oss_bucket') ?: config('services.oss.bucket', '')),
                (string) (Settings::get('offload.offload_oss_endpoint') ?: config('services.oss.endpoint', 'https://oss-cn-hangzhou.aliyuncs.com')),
            ),
            'tencent_cos' => new TencentCosClient(
                (string) (Settings::get('offload.offload_cos_secret_id') ?: config('services.cos.secret_id', '')),
                (string) (Settings::get('offload.offload_cos_secret_key') ?: config('services.cos.secret_key', '')),
                (string) (Settings::get('offload.offload_cos_bucket') ?: config('services.cos.bucket', '')),
                (string) (Settings::get('offload.offload_cos_region') ?: config('services.cos.region', 'ap-guangzhou')),
            ),
            default => new S3Client(
                (string) (Settings::get('offload.offload_s3_key') ?: PluginManager::setting('offload', 's3_access_key', '')),
                (string) (Settings::get('offload.offload_s3_secret') ?: PluginManager::setting('offload', 's3_secret_key', '')),
                (string) (Settings::get('offload.offload_s3_bucket') ?: PluginManager::setting('offload', 's3_bucket', 'monit-replays')),
                (string) (Settings::get('offload.offload_s3_region') ?: PluginManager::setting('offload', 's3_region', 'us-east-1')),
                (string) (Settings::get('offload.offload_s3_endpoint') ?: PluginManager::setting('offload', 's3_endpoint', '')),
            ),
        };
    }

    /** 当前驱动凭据是否已配置（供 Cron 命令前置检查） */
    public static function isConfigured(): bool
    {
        [$id, $secret] = match (static::driver()) {
            'aliyun_oss' => [
                Settings::get('offload.offload_oss_access_key_id') ?: config('services.oss.access_key_id'),
                Settings::get('offload.offload_oss_access_key_secret') ?: config('services.oss.access_key_secret'),
            ],
            'tencent_cos' => [
                Settings::get('offload.offload_cos_secret_id') ?: config('services.cos.secret_id'),
                Settings::get('offload.offload_cos_secret_key') ?: config('services.cos.secret_key'),
            ],
            default => [
                Settings::get('offload.offload_s3_key') ?: PluginManager::setting('offload', 's3_access_key', ''),
                Settings::get('offload.offload_s3_secret') ?: PluginManager::setting('offload', 's3_secret_key', ''),
            ],
        };

        return filled($id) && filled($secret);
    }
}
