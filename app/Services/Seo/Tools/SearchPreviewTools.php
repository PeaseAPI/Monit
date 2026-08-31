<?php

namespace App\Services\Seo\Tools;

/**
 * 搜索结果预览工具组：按各引擎截断规则生成标题/描述展示效果
 */
class SearchPreviewTools
{
    protected function preview(array $in, int $titleMax, int $descMax, string $engine): array
    {
        $title = trim((string) ($in['title'] ?? ''));
        $url = trim((string) ($in['url'] ?? ''));
        $description = trim((string) ($in['description'] ?? ''));

        return ['ok' => true, 'data' => [
            '引擎' => $engine,
            '标题长度' => mb_strlen($title).' / '.$titleMax,
            '描述长度' => mb_strlen($description).' / '.$descMax,
            '标题超限' => mb_strlen($title) > $titleMax ? '是（将被截断）' : '否',
            '描述超限' => mb_strlen($description) > $descMax ? '是（将被截断）' : '否',
        ], 'text' => "【{$title}】\n{$url}\n{$description}"];
    }

    public function googlePreview(array $in): array
    {
        return $this->preview($in, 60, 160, 'Google');
    }

    public function bingPreview(array $in): array
    {
        return $this->preview($in, 60, 160, 'Bing');
    }

    public function yandexPreview(array $in): array
    {
        return $this->preview($in, 55, 160, 'Yandex');
    }

    public function yahooPreview(array $in): array
    {
        return $this->preview($in, 60, 160, 'Yahoo');
    }
}
