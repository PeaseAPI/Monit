<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * 动态OG图片生成服务（规格书 §14.7：dynamic-og-images 插件）
 */
class DynamicOgImageService
{
    public function generate(string $type, int $id)
    {
        $title = match ($type) {
            'blog' => $this->getBlogTitle($id),
            'page' => $this->getPageTitle($id),
            'website' => $this->getWebsiteTitle($id),
            default => config('app.name'),
        };

        $manager = new ImageManager(new Driver());
        $image = $manager->create(1200, 630);

        // 背景渐变
        $image->fill('#4f46e5');

        // 标题文字
        $image->text($title, 600, 315, function ($font) {
            $font->filename(public_path('fonts/NotoSansSC-Regular.ttf'));
            $font->size(48);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('center');
        });

        return $image->toPng()->toResponse();
    }

    private function getBlogTitle(int $id): string
    {
        $post = \App\Models\BlogPost::find($id);
        return $post?->title ?? config('app.name');
    }

    private function getPageTitle(int $id): string
    {
        $page = \App\Models\Page::find($id);
        return $page?->title ?? config('app.name');
    }

    private function getWebsiteTitle(int $id): string
    {
        $website = \App\Models\Website::find($id);
        return $website?->name ?? config('app.name');
    }
}
