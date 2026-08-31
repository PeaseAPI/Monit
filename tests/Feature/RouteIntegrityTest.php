<?php

namespace Tests\Feature;

use App\Models\BlogPostsCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * 完整性回归测试（M28 全量体检修复）
 * - 引用的路由名必须已注册：防止"增删改后 redirect/表单 500"（stats.* 同 URI 路由名被遮蔽类 bug）
 * - 引用的语言键必须存在于 en.json：防止页面直接显示键名
 * - 博客分类管理页可渲染：此前 view('admin.blog_post.categories') 视图缺失导致 500
 */
class RouteIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_referenced_route_names_are_registered(): void
    {
        $registered = collect(Route::getRoutes())
            ->map(fn ($r) => $r->getName())
            ->filter()
            ->all();

        $referenced = [];

        $finder = (new Finder)
            ->files()
            ->in([resource_path('views'), app_path('Http/Controllers')])
            ->name('/\.(blade\.php|php)$/');

        foreach ($finder as $file) {
            preg_match_all("/route\(\s*'([a-zA-Z0-9_.\-]+)'/", $file->getContents(), $m);

            foreach ($m[1] as $name) {
                if (str_starts_with($name, 'admin.plugins.')) {
                    continue; // 插件路由由插件系统在安装/激活时动态注册
                }

                $referenced[$name] = $file->getRelativePathname();
            }
        }

        $missing = array_diff(array_keys($referenced), $registered);

        $this->assertSame([], $missing, '以下路由名被引用但未注册: '.implode(', ', $missing));
    }

    public function test_referenced_lang_keys_exist(): void
    {
        $keys = array_keys(json_decode((string) file_get_contents(lang_path('en.json')), true));

        $used = [];

        $finder = (new Finder)
            ->files()
            ->in([resource_path('views'), app_path(), base_path('routes'), base_path('plugins')])
            ->name('/\.(blade\.php|php)$/');

        foreach ($finder as $file) {
            preg_match_all(
                "/(?:__|trans|trans_choice|lang)\(\s*'([a-zA-Z0-9_.\-]+)'|@lang\('([a-zA-Z0-9_.\-]+)'/",
                $file->getContents(),
                $m,
            );

            foreach ($m[1] as $name) {
                $used[$name] = $file->getRelativePathname();
            }

            foreach ($m[2] as $name) {
                $used[$name] = $file->getRelativePathname();
            }
        }

        $skipNamespaces = ['validation', 'auth', 'pagination', 'passwords'];

        $missing = [];

        foreach ($used as $key => $file) {
            if (! str_contains($key, '.')) {
                continue; // 纯文案键（JSON 原文键）
            }

            if (str_ends_with($key, '_')) {
                continue; // 动态前缀拼接（如 'admin.broadcast_status_'.$x）
            }

            if (in_array(explode('.', $key)[0], $skipNamespaces, true)) {
                continue; // PHP 内建翻译命名空间
            }

            if (! in_array($key, $keys, true)) {
                $missing[$key] = $file;
            }
        }

        $this->assertSame([], $missing, '以下语言键被引用但 lang/en.json 缺失: '.implode(', ', array_keys($missing)));
    }

    public function test_admin_blog_categories_page_renders(): void
    {
        $admin = User::create([
            'name' => 'M28',
            'email' => 'm28@example.com',
            'password' => bcrypt('x'),
            'status' => 1,
            'plan_id' => 'free',
            'type' => 1,
        ]);

        BlogPostsCategory::create([
            'title' => 'News',
            'url' => 'news',
            'order' => 1,
            'user_id' => $admin->user_id,
            'datetime' => now(),
        ]);

        $this->actingAs($admin, 'web')
            ->get('/admin/blog-posts-categories')
            ->assertOk()
            ->assertSee('News');
    }
}
