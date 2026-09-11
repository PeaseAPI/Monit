<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Page;
use App\Models\Website;
use Illuminate\Http\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;

/**
 * 动态OG图片生成服务（规格书 §14.7：dynamic-og-images 插件）
 */
class DynamicOgImageService
{
    /**
     * 标题中文字体（Noto Sans SC；部署时需放置该文件，缺失时降级为纯背景图）
     */
    private const FONT_PATH = 'fonts/NotoSansSC-Regular.ttf';

    /**
     * 生成 OG 分享图（1200x630 PNG 响应）
     */
    public function generate(string $type, int $id): Response
    {
        $fontPath = public_path(self::FONT_PATH);

        // v4 无内置字体回退：字体文件缺失时跳过文字绘制（省去标题查询），仅输出背景图
        if (! is_file($fontPath)) {
            return $this->render('#4f46e5', null, null);
        }

        $title = match ($type) {
            'blog' => $this->getBlogTitle($id),
            'page' => $this->getPageTitle($id),
            'website' => $this->getWebsiteTitle($id),
            default => config('app.name'),
        };

        return $this->render('#4f46e5', $title, $fontPath);
    }

    /**
     * 绘制并编码 PNG 响应
     *
     * @param  null|string  $title  标题文本（null 时不绘制文字）
     * @param  null|string  $fontPath  字体文件绝对路径（$title 非空时必传）
     */
    private function render(string $background, ?string $title, ?string $fontPath): Response
    {
        $manager = new ImageManager(new Driver);
        $image = $manager->createImage(1200, 630);

        // 背景
        $image->fill($background);

        // 标题文字
        if ($title !== null && $fontPath !== null) {
            $image->text($title, 600, 315, function (FontFactory $font) use ($fontPath): void {
                $font->filename($fontPath);
                $font->size(48);
                $font->color('#ffffff');
                $font->align('center', 'center');
            });
        }

        $png = $image->encodeUsingFormat(Format::PNG);

        return response((string) $png)->header('Content-Type', 'image/png');
    }

    private function getBlogTitle(int $id): string
    {
        $post = BlogPost::find($id);

        return $post?->title ?? config('app.name');
    }

    private function getPageTitle(int $id): string
    {
        $page = Page::find($id);

        return $page?->title ?? config('app.name');
    }

    private function getWebsiteTitle(int $id): string
    {
        $website = Website::find($id);

        return $website?->name ?? config('app.name');
    }
}
