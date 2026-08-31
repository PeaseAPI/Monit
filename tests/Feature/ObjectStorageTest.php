<?php

namespace Tests\Feature;

use App\Services\Sms\SmsService;
use App\Support\AliyunOssClient;
use App\Support\ObjectStorage;
use App\Support\S3Client;
use App\Support\Settings;
use App\Support\TencentCosClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * M17 §14.8 对象存储扩展：ObjectStorage 工厂 + 阿里云 OSS / 腾讯云 COS 客户端
 */
class ObjectStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_driver_is_s3(): void
    {
        $this->assertSame('s3', ObjectStorage::driver());
        $this->assertInstanceOf(S3Client::class, ObjectStorage::make());
    }

    public function test_driver_resolves_aliyun_oss(): void
    {
        Settings::set('offload.offload_storage_driver', 'aliyun_oss');
        Settings::set('offload.offload_oss_access_key_id', 'LTAI5tx');
        Settings::set('offload.offload_oss_access_key_secret', 'secret');
        Settings::set('offload.offload_oss_bucket', 'monit-replays');
        Settings::set('offload.offload_oss_endpoint', 'https://oss-cn-beijing.aliyuncs.com');

        $this->assertSame('aliyun_oss', ObjectStorage::driver());
        $this->assertTrue(ObjectStorage::isConfigured());
        $this->assertInstanceOf(AliyunOssClient::class, ObjectStorage::make());
    }

    public function test_driver_resolves_tencent_cos(): void
    {
        Settings::set('offload.offload_storage_driver', 'tencent_cos');
        Settings::set('offload.offload_cos_secret_id', 'AKIDxxxx');
        Settings::set('offload.offload_cos_secret_key', 'secret');
        Settings::set('offload.offload_cos_bucket', 'monit-1250000000');
        Settings::set('offload.offload_cos_region', 'ap-shanghai');

        $this->assertSame('tencent_cos', ObjectStorage::driver());
        $this->assertTrue(ObjectStorage::isConfigured());
        $this->assertInstanceOf(TencentCosClient::class, ObjectStorage::make());
    }

    public function test_aliyun_oss_url_is_virtual_hosted(): void
    {
        $client = new AliyunOssClient('key', 'secret', 'monit-replays', 'https://oss-cn-hangzhou.aliyuncs.com');

        $this->assertSame(
            'https://monit-replays.oss-cn-hangzhou.aliyuncs.com/replays/1/100.json',
            $client->urlFor('replays/1/100.json'),
        );
    }

    public function test_tencent_cos_url_is_virtual_hosted(): void
    {
        $client = new TencentCosClient('id', 'key', 'monit-1250000000', 'ap-guangzhou');

        $this->assertSame(
            'https://monit-1250000000.cos.ap-guangzhou.myqcloud.com/replays/1/100.json',
            $client->urlFor('replays/1/100.json'),
        );
    }

    public function test_phone_normalization(): void
    {
        $this->assertSame('13800138000', SmsService::normalizePhone('13800138000'));
        $this->assertSame('13800138000', SmsService::normalizePhone('+8613800138000'));
        $this->assertSame('13800138000', SmsService::normalizePhone('+86 138-0013-8000'));
        $this->assertSame('13800138000', SmsService::normalizePhone(' 13800138000 '));

        $this->assertTrue(SmsService::isPhone('+8613800138000'));
        $this->assertFalse(SmsService::isPhone('not-a-phone'));
        $this->assertFalse(SmsService::isPhone('12345'));
    }
}
