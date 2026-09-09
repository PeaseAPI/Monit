<?php

namespace Tests\Feature;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A3：帮助中心后台 CRUD + 前台展示（仿帮助中心样式，无数据回退静态内容）
 */
class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertNotNull($article->url);

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
        $this->assertSame(1, $article->fresh()->views);
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
        $this->assertSame('新分类', $category->fresh()->title);

        // 删除分类后文章保留为未分类
        $this->actingAs($admin)->delete('/admin/help-categories/'.$category->category_id)->assertSessionHas('success');
        $this->assertNull($article->fresh()->category_id);
        $this->assertDatabaseMissing('help_categories', ['category_id' => $category->category_id]);
    }
}
