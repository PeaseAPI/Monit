<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 页面级 SEO meta / Open Graph 回归（安全审计 SEO 收尾）
 *
 * 覆盖：parts/brand_head 的 og:site_name/og:title/og:type/og:url/og:description/
 * twitter:card 全组渲染；页面级 @section 覆盖链：
 * - /seo（获客页）：title/meta_description/canonical 三 section 齐备
 * - blog_post：description 字段优先，空则内容截断 157 字符；canonical 指向文章 URL
 * - page（CMS）：同 blog_post 逻辑
 */
class MetaTagsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductionSeeder::class);
    }

    private function author(): User
    {
        return User::firstOrCreate(
            ['email' => 'meta-author@example.com'],
            ['name' => 'Meta Author', 'password' => bcrypt('x'), 'status' => 1, 'plan_id' => 'free']
        );
    }

    public function test_seo_landing_renders_full_og_group(): void
    {
        $response = $this->get('/seo');
        $response->assertOk();

        $html = $response->getContent();
        $this->assertStringContainsString('<meta property="og:site_name"', $html);
        $this->assertStringContainsString('<meta property="og:type" content="website">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('seo.landing').'"', $html);
        $this->assertStringContainsString('<meta property="og:description" content="'.__('seo.landing_description').'"', $html);
        // og:title 跟随 title section：页面标题 + 分隔符 + 站名
        $expectedOgTitle = __('seo.landing_title').' '.\App\Support\Brand::titleSeparator().' '.\App\Support\Brand::name();
        $this->assertMatchesRegularExpression(
            '#<meta property="og:title" content="'.preg_quote($expectedOgTitle, '#').'"#',
            $html,
            'og:title 应为「title section · 站名」结构'
        );
        // 无 og_image 配置时 twitter:card 退化为 summary
        $this->assertStringContainsString('<meta name="twitter:card" content="summary">', $html);
    }

    public function test_index_og_title_falls_back_to_site_name_when_no_title_section(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        // 首页未声明 title section → og:title 退化为站名（不硬编码工具页文案）
        $this->assertMatchesRegularExpression(
            '#<meta property="og:title" content="'.preg_quote(\App\Support\Brand::name(), '#').'"#',
            $html
        );
    }

    public function test_blog_post_meta_uses_description_field_and_canonical(): void
    {
        BlogPost::create([
            'user_id' => $this->author()->user_id,
            'title' => 'Meta 测试文章',
            'url' => 'meta-test-post',
            'content' => '<p>正文正文正文正文正文正文</p>',
            'description' => '这是文章的 SEO 描述',
            'type' => 'blog',
            'is_published' => true,
            'datetime' => now(),
        ]);

        $html = $this->get('/blog/meta-test-post')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<meta name="description" content="这是文章的 SEO 描述">',
            $html,
            'meta_description 应优先取 description 字段'
        );
        $this->assertStringContainsString(
            '<link rel="canonical" href="'.route('blog.post', 'meta-test-post').'">',
            $html
        );
        $this->assertStringContainsString('<meta property="og:url" content="'.route('blog.post', 'meta-test-post').'">', $html);
        $this->assertStringContainsString('<title>Meta 测试文章', $html);
        $this->assertMatchesRegularExpression(
            '#<meta property="og:title" content="Meta 测试文章 '.preg_quote(\App\Support\Brand::titleSeparator(), '#').' '.preg_quote(\App\Support\Brand::name(), '#').'"#',
            $html
        );
    }

    public function test_blog_post_without_description_falls_back_to_content_excerpt(): void
    {
        BlogPost::create([
            'user_id' => $this->author()->user_id,
            'title' => '无描述文章',
            'url' => 'no-desc-post',
            'content' => '<p>内容开头的这段文字应当被截取为 meta 描述</p>',
            'description' => null,
            'type' => 'blog',
            'is_published' => true,
            'datetime' => now(),
        ]);

        $html = $this->get('/blog/no-desc-post')->assertOk()->getContent();

        $this->assertStringContainsString(
            '<meta name="description" content="内容开头的这段文字应当被截取为 meta 描述">',
            $html,
            'description 为空时应回退到内容 strip_tags 截断'
        );
    }

    public function test_cms_page_renders_meta_and_canonical(): void
    {
        Page::create([
            'user_id' => $this->author()->user_id,
            'title' => '关于我们',
            'url' => 'about',
            'content' => '<p>页面正文</p>',
            'description' => '关于页面的描述',
            'type' => 'page',
            'position' => 1,
            'order' => 1,
            'is_published' => true,
            'datetime' => now(),
        ]);

        $html = $this->get('/page/about')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description" content="关于页面的描述">', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('page', 'about').'">', $html);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('page', 'about').'">', $html);
    }
}
