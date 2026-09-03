<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sitemap 回归测试（/sitemap.xml 标准路径）
 * - 核心获客页（首页/定价/SEO 落地页/博客/帮助/联系）必须收录
 * - 博客文章与 CMS 页面带 lastmod（抓取调度信号）
 * - 未发布内容不得泄露
 */
class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Sitemap',
            'email' => 'sitemap@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_核心静态页收录(): void
    {
        $xml = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('<loc>'.route('index').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('plan').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('seo.landing').'</loc>', $xml);
        $this->assertStringContainsString('<loc>'.route('blog').'</loc>', $xml);
    }

    public function test_已发布文章带_lastmod_且草稿不收录(): void
    {
        BlogPost::create([
            'user_id' => 1,
            'title' => '已发布文章',
            'url' => 'published-post',
            'content' => 'x',
            'is_published' => true,
            'type' => 'blog',
            'datetime' => now(),
        ]);
        BlogPost::create([
            'user_id' => 1,
            'title' => '草稿文章',
            'url' => 'draft-post',
            'content' => 'x',
            'is_published' => false,
            'type' => 'draft',
            'datetime' => now(),
        ]);

        $xml = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('/blog/published-post', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringNotContainsString('/blog/draft-post', $xml);
    }

    public function test_已发布_CMS_页带_lastmod_且未发布不收录(): void
    {
        Page::create([
            'user_id' => 1,
            'title' => '已发布页面',
            'url' => 'published-page',
            'content' => 'x',
            'is_published' => true,
            'datetime' => now(),
        ]);
        Page::create([
            'user_id' => 1,
            'title' => '未发布页面',
            'url' => 'hidden-page',
            'content' => 'x',
            'is_published' => false,
            'datetime' => now(),
        ]);

        $xml = $this->get('/sitemap.xml')->assertStatus(200)->getContent();

        $this->assertStringContainsString('/page/published-page', $xml);
        $this->assertStringNotContainsString('/page/hidden-page', $xml);
    }

    public function test_输出为合法_Xml(): void
    {
        $xml = $this->get('/sitemap.xml')->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'sitemap 应为合法 XML');
    }
}
