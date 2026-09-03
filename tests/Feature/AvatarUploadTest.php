<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * 头像上传安全回归（安全审计 · AccountController::update）
 *
 * 历史漏洞：getClientOriginalExtension()（客户端可控）直接用于落盘文件名，
 * 而 image/mimes 规则只做内容嗅探（finfo）——「GIF89a 头 + HTML/script」的
 * 多态文件可整体通过验证，以 .html/.shtml 扩展名落盘 public/uploads/avatars/
 * （Web 直达目录）→ 浏览器按扩展名以 text/html 渲染 → 存储型 XSS。
 * （.php/.phtml 系另由 Laravel validateMimes 内建 shouldBlockPhpUpload 拦截，
 * 本测试仍断言其不落盘：不依赖框架内部实现细节的纵深防御。）
 *
 * 本测试用 UploadedFile 真实构造（验证规则走 finfo 嗅内容，与线上行为一致），
 * 而非 fake()（fake 的 MIME 按文件名映射，测不出该攻击面）。
 */
class AvatarUploadTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @var string[] 测试期间落盘的文件，tearDown 清理 */
    private array $written = [];

    protected function tearDown(): void
    {
        foreach ($this->written as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    private function makeUser(): User
    {
        return User::create([
            'name' => 'Avatar Tester',
            'email' => 'avatar-'.Str::random(8).'@example.com',
            'password' => bcrypt('secret123'),
            'status' => 1,
            'plan_id' => 'free',
        ]);
    }

    /**
     * 构造与线上攻击完全一致的请求文件：
     * mimeType 参数是客户端声明（仅存于 $_FILES['type']，验证规则不使用），
     * guessExtension()/image 规则走 finfo 嗅真实内容 → GIF89a 头即判定 image/gif。
     */
    private function attackFile(string $clientName, string $content): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'avpoc');
        file_put_contents($tmp, $content);

        return new UploadedFile($tmp, $clientName, 'image/gif', null, true);
    }

    private function postProfile(User $user, array $payload)
    {
        return $this->actingAs($user)->post('/account', array_merge([
            '_method' => 'PUT',
            'name' => $user->name,
            'email' => $user->email,
        ], $payload));
    }

    private function avatarDir(): string
    {
        return public_path('uploads/avatars');
    }

    public function test_php_polyglot_webshell_is_rejected(): void
    {
        $user = $this->makeUser();

        // GIF89a 魔数过 finfo；尾部 PHP 代码在 php-fpm 执行整个文件时生效
        $webshell = $this->attackFile(
            'avatar.php',
            "GIF89a<?php echo 'PWNED:'.php_uname(); ?>"
        );

        $this->postProfile($user, ['avatar' => $webshell])
            ->assertSessionHasErrors('avatar');

        $this->assertSame([], glob($this->avatarDir().'/*.php'), '任何 .php 不得落盘');
        $this->assertNull($user->fresh()->avatar);
    }

    public function test_dangerous_extensions_are_all_rejected(): void
    {
        $user = $this->makeUser();

        foreach (['phtml', 'phar', 'shtml', 'html', 'php5', 'php7', 'pht', 'svg'] as $ext) {
            $file = $this->attackFile(
                'avatar.'.$ext,
                "GIF89a<html><script>alert(document.domain)</script>"
            );

            $this->postProfile($user, ['avatar' => $file])
                ->assertSessionHasErrors('avatar', "扩展名 .{$ext} 应被白名单拒绝");

            $this->assertSame(
                [],
                glob($this->avatarDir().'/*.'.$ext),
                ".{$ext} 不得落盘"
            );
        }
    }

    public function test_valid_png_avatar_uploads_and_persists(): void
    {
        $user = $this->makeUser();

        $response = $this->postProfile($user, [
            'avatar' => UploadedFile::fake()->image('avatar.png', 64, 64),
        ]);

        $response->assertRedirect();
        $user = $user->fresh();
        $this->assertNotNull($user->avatar);
        $this->assertMatchesRegularExpression(
            '#^/uploads/avatars/user_\d+_[0-9a-zA-Z]{16}\.png$#',
            $user->avatar
        );

        $path = public_path(ltrim($user->avatar, '/'));
        $this->written[] = $path;
        $this->assertFileExists($path);
        $this->assertSame('png', strtolower(pathinfo($path, PATHINFO_EXTENSION)));
    }

    public function test_uppercase_whitelisted_extension_is_accepted_and_normalized(): void
    {
        $user = $this->makeUser();

        $tmp = tempnam(sys_get_temp_dir(), 'avup');
        $img = imagecreatetruecolor(8, 8);
        imagepng($img, $tmp);
        $file = new UploadedFile($tmp, 'avatar.PNG', 'image/png', null, true);

        $this->postProfile($user, ['avatar' => $file])->assertRedirect();

        $avatar = $user->fresh()->avatar;
        $this->assertNotNull($avatar);
        $this->assertStringEndsWith('.png', $avatar, '扩展名应统一小写');
        $path = public_path(ltrim($avatar, '/'));
        $this->written[] = $path;
        $this->assertFileExists($path);
    }

    public function test_repeated_upload_replaces_old_file_with_distinct_random_name(): void
    {
        $user = $this->makeUser();

        $this->postProfile($user, [
            'avatar' => UploadedFile::fake()->image('a.png', 8, 8),
        ])->assertRedirect();
        $firstPath = public_path(ltrim($user->fresh()->avatar, '/'));

        // 无 sleep：同一秒内连续上传，历史上 time() 文件名会互相覆盖
        $this->postProfile($user, [
            'avatar' => UploadedFile::fake()->image('b.png', 8, 8),
        ])->assertRedirect();

        $user = $user->fresh();
        $secondPath = public_path(ltrim($user->avatar, '/'));
        $this->written[] = $secondPath;

        $this->assertNotEquals($firstPath, $secondPath, '随机文件名：同秒上传不得重名');
        $this->assertFileExists($secondPath);
        $this->assertFileDoesNotExist($firstPath, '旧头像应被替换清理');
    }

    public function test_avatar_remove_clears_file_and_db(): void
    {
        $user = $this->makeUser();

        $this->postProfile($user, [
            'avatar' => UploadedFile::fake()->image('x.png', 8, 8),
        ])->assertRedirect();
        $path = public_path(ltrim($user->fresh()->avatar, '/'));
        $this->assertFileExists($path);

        $this->postProfile($user, ['avatar_remove' => '1'])->assertRedirect();

        $this->assertNull($user->fresh()->avatar);
        $this->assertFileDoesNotExist($path);
    }

    public function test_oversized_dimension_image_is_rejected(): void
    {
        $user = $this->makeUser();

        // 超过 4096px 上限（解压炸弹防护）
        $file = UploadedFile::fake()->image('big.png', 5000, 5000);

        $this->postProfile($user, ['avatar' => $file])
            ->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatar);
    }
}
