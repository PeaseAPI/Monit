<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 用户反馈 #21：品牌文件上传后端处理测试
 *
 * 验证 AdminSettings 控制器能正确处理 logo/favicon/logo_dark 文件上传，
 * 存储到 public disk，并用本地 URL 覆盖对应 _url 字段。
 */
class BrandingUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $admin = \App\Models\User::create([
            'name' => 'Admin Test',
            'email' => 'admin@branding-upload.test',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
            'type' => 1,
        ]);
        $this->actingAs($admin);
    }

    #[Test]
    public function logo_upload_stores_file_and_sets_url(): void
    {
        $file = UploadedFile::fake()->image('logo.png', 200, 60);

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'logo_upload' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        Settings::flush();
        // 验证 URL 字段已设置为 /storage/branding/... 格式
        $logoUrl = Settings::get('branding.logo_url', '');
        $this->assertStringStartsWith('/storage/branding/', $logoUrl);
        $this->assertStringContainsString('.png', $logoUrl);

        // 验证文件实际存在于 public disk
        $path = str_replace('/storage/', '', $logoUrl);
        Storage::disk('public')->assertExists($path);
    }

    #[Test]
    public function favicon_upload_stores_file_and_sets_url(): void
    {
        $file = UploadedFile::fake()->create('favicon.ico', 100, 'image/x-icon');

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'favicon_upload' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        Settings::flush();
        $faviconUrl = Settings::get('branding.favicon_url', '');
        $this->assertStringStartsWith('/storage/branding/', $faviconUrl);
    }

    #[Test]
    public function logo_dark_upload_stores_file_and_sets_url(): void
    {
        $file = UploadedFile::fake()->image('logo-dark.png', 200, 60);

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'logo_dark_upload' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        Settings::flush();
        $logoDarkUrl = Settings::get('branding.logo_dark_url', '');
        $this->assertStringStartsWith('/storage/branding/', $logoDarkUrl);
    }

    #[Test]
    public function url_only_works_without_upload(): void
    {
        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'logo_url' => 'https://cdn.example.com/logo.png',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // Settings::get 通过自愈逻辑正确读取
        Settings::flush();
        $this->assertSame(
            'https://cdn.example.com/logo.png',
            Settings::get('branding.logo_url', '')
        );
    }

    #[Test]
    public function upload_overrides_url(): void
    {
        // 先设置一个 URL
        Settings::set('branding.logo_url', 'https://cdn.example.com/old-logo.png');

        $file = UploadedFile::fake()->image('new-logo.png', 200, 60);

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'logo_url' => 'https://cdn.example.com/old-logo.png',
            'logo_upload' => $file,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        // 上传优先：URL 应该被本地路径覆盖
        Settings::flush();
        $logoUrl = Settings::get('branding.logo_url', '');
        $this->assertStringStartsWith('/storage/branding/', $logoUrl);
    }

    #[Test]
    public function invalid_file_type_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload');

        $response = $this->put(route('admin.settings.update'), [
            'group' => 'branding',
            'site_name' => 'TestSite',
            'logo_upload' => $file,
        ]);

        $response->assertSessionHasErrors('logo_upload');
    }
}

