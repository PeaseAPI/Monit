<?php

namespace Tests\Feature;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Database\Seeders\HelpCenterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A3：帮助中心后台 CRUD + 前台展示（仿帮助中心样式，无数据回退静态内容）
 */
class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function makeUser(array $attrs = []): User
    {
        return User::create(array_merge([
            'name' => 'HC 用户', 'email' => uniqid('hc-').'@help.test',
            'password' => bcrypt('secret123'), 'status' => 1, 'plan_id' => 'free', 'type' => 0,
        ], $attrs));
    }

    public function test_guest_sees_fallback_content_when_no_data(): void
    {
        $this->get('/help')
            ->assertOk()
            ->assertSee(__('help.install_tracking'))
            ->assertSee(__('help.search_placeholder'));
    }

    public function test_admin_can_create_category_and_article_then_public_shows_them(): void
    {
        $admin = $this->makeUser(['type' => 1, 'email' => 'hc-admin@help.test']);

        $this->actingAs($admin)->post('/admin/help-categories', [
            'title' => '快速上手', 'url' => 'getting-started', 'order' => 1,
        ])->assertSessionHas('success');

        $category = HelpCategory::where('url', 'getting-started')->firstOrFail();

        $this->actingAs($admin)->post('/admin/help-articles', [
            'title' => '如何安装统计代码',
            'category_id' => $category->category_id,
            'content' => '<p>复制像素代码到 &lt;head&gt; 标签内。</p>',
            'description' => '三步完成统计代码安装',
            'order' => 1,
            'is_published' => '1',
        ])->assertSessionHas('success');

        $article = HelpArticle::where('title', '如何安装统计代码')->firstOrFail();

        // 前台帮助中心展示分类与文章
        $this->get('/help')
            ->assertOk()
            ->assertSee('快速上手')
            ->assertSee('如何安装统计代码');

        // 详情页可访问 + 浏览计数
        $this->get('/help/article/'.$article->url)
            ->assertOk()
            ->assertSee('如何安装统计代码')
            ->assertSee('三步完成统计代码安装');
        $this->assertSame(1, $this->freshModel($article)->views);
    }

    public function test_unpublished_articles_are_hidden_from_public(): void
    {
        $admin = $this->makeUser(['type' => 1, 'email' => 'hc-admin2@help.test']);
        HelpArticle::create([
            'user_id' => $admin->user_id, 'title' => '未发布文章', 'url' => 'draft-article',
            'content' => '<p>draft</p>', 'is_published' => false, 'datetime' => now(),
        ]);

        $this->get('/help')->assertOk()->assertDontSee('未发布文章');
        $this->get('/help/article/draft-article')->assertStatus(404);
    }

    public function test_non_admin_cannot_access_help_admin_pages(): void
    {
        $user = $this->makeUser(['email' => 'hc-user@help.test']);

        $this->actingAs($user)->get('/admin/help-categories')->assertStatus(403);
        $this->actingAs($user)->get('/admin/help-articles')->assertStatus(403);
    }

    public function test_admin_category_update_and_delete(): void
    {
        $admin = $this->makeUser(['type' => 1, 'email' => 'hc-admin3@help.test']);

        $category = HelpCategory::create([
            'user_id' => $admin->user_id, 'title' => '旧分类', 'url' => 'old-cat', 'order' => 0, 'datetime' => now(),
        ]);
        $article = HelpArticle::create([
            'user_id' => $admin->user_id, 'category_id' => $category->category_id,
            'title' => '从属文章', 'url' => 'owned-article', 'content' => 'x', 'is_published' => true, 'datetime' => now(),
        ]);

        $this->actingAs($admin)->put('/admin/help-categories/'.$category->category_id, [
            'title' => '新分类', 'url' => 'new-cat', 'order' => 2,
        ])->assertSessionHas('success');
        $this->assertSame('新分类', $this->freshModel($category)->title);

        // 删除分类后文章保留为未分类
        $this->actingAs($admin)->delete('/admin/help-categories/'.$category->category_id)->assertSessionHas('success');
        $this->assertNull($this->freshModel($article)->category_id);
        $this->assertDatabaseMissing('help_categories', ['category_id' => $category->category_id]);
    }

    /**
     * Round 10：官方文档种子幂等入库（10 分类 / 45 篇），前台完整可见
     */
    public function test_help_center_seeder_is_idempotent_and_public(): void
    {
        $this->seed(HelpCenterSeeder::class);
        $this->seed(HelpCenterSeeder::class); // 重复执行不产生重复数据

        $this->assertSame(10, HelpCategory::count());
        $this->assertSame(45, HelpArticle::count());

        // 未分类之外的每篇文章都有归属分类
        $this->assertSame(45, HelpArticle::whereNotNull('category_id')->count());
        $this->assertSame(45, HelpArticle::where('is_published', true)->count());

        // 前台首页展示分类与文章
        $this->get('/help')
            ->assertOk()
            ->assertSee(__('help.doc_nav'))
            ->assertSee('快速入门')
            ->assertSee('管理员指南')
            ->assertSee('五分钟接入：从注册到看到数据')
            ->assertSee('系统设置详解（全部设置分组）');
    }

    /**
     * Round 10：详情页三栏文档布局（左目录树 + 右本页目录容器 + 上下篇）
     */
    public function test_article_detail_renders_doc_layout_and_toc(): void
    {
        $this->seed(HelpCenterSeeder::class);

        $article = HelpArticle::where('url', 'quick-start')->firstOrFail();

        $this->get('/help/article/'.$article->url)
            ->assertOk()
            ->assertSee('help-doc')                       // 正文排版作用域
            ->assertSee('help-toc')                       // 右栏本页目录容器
            ->assertSee(__('help.on_this_page'))
            ->assertSee(__('help.doc_nav'))
            ->assertSee('help-nav')                       // 左侧目录树
            // 正文内含提示条 / 步骤 / 代码块组件
            ->assertSee('doc-note')
            ->assertSee('doc-steps')
            // 同分类上下篇导航存在
            ->assertSee(__('help.prev_article'))
            ->assertSee(__('help.next_article'));
    }
}
