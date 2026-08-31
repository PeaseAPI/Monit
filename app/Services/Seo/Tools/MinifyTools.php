<?php

namespace App\Services\Seo\Tools;

/**
 * 压缩与格式化工具组
 */
class MinifyTools
{
    public function htmlMinifier(array $in): array
    {
        $code = (string) ($in['code'] ?? '');

        if (trim($code) === '') {
            return ['ok' => false, 'error' => '请输入 HTML', 'data' => []];
        }

        $minified = preg_replace(
            ['/<!--(?!\[if).*?--> /s', '/>\s+</', '/\s{2,}/'],
            ['', '><', ' '],
            $code
        ) ?? $code;

        return $this->ratio($code, trim($minified));
    }

    public function cssMinifier(array $in): array
    {
        $code = (string) ($in['code'] ?? '');

        if (trim($code) === '') {
            return ['ok' => false, 'error' => '请输入 CSS', 'data' => []];
        }

        $minified = preg_replace(
            ['/\/\*.*?\*\//s', '/\s*([{}:;,>])\s*/', '/\s{2,}/'],
            ['', '$1', ' '],
            $code
        ) ?? $code;

        return $this->ratio($code, trim($minified));
    }

    public function jsMinifier(array $in): array
    {
        $code = (string) ($in['code'] ?? '');

        if (trim($code) === '') {
            return ['ok' => false, 'error' => '请输入 JS', 'data' => []];
        }

        // 保守压缩：去注释与多余空白（不破坏字符串字面量之外的换行依赖结构）
        $minified = preg_replace(
            ['/\/\/[^\n]*/', '/\/\*.*?\*\//s', "/\n\s*/"],
            ['', '', "\n"],
            $code
        ) ?? $code;

        return $this->ratio($code, trim($minified));
    }

    protected function ratio(string $before, string $after): array
    {
        return ['ok' => true, 'data' => [
            '原始大小' => number_format(strlen($before) / 1024, 2).' KB',
            '压缩后' => number_format(strlen($after) / 1024, 2).' KB',
            '节省' => strlen($before) > 0 ? round((1 - strlen($after) / strlen($before)) * 100, 1).'%' : '0%',
        ], 'text' => $after];
    }

    public function jsonValidator(array $in): array
    {
        $code = trim((string) ($in['code'] ?? ''));

        if ($code === '') {
            return ['ok' => false, 'error' => '请输入 JSON', 'data' => []];
        }

        $decoded = json_decode($code);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'error' => 'JSON 无效：'.json_last_error_msg(), 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '结论' => 'JSON 有效',
            '类型' => static::typeOf($decoded),
        ], 'text' => json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    protected static function typeOf(mixed $value): string
    {
        return match (true) {
            is_array($value) => array_is_list($value) ? '数组' : '对象',
            is_string($value) => '字符串',
            is_int($value) => '整数',
            is_float($value) => '浮点数',
            is_bool($value) => '布尔值',
            $value === null => 'null',
            default => '未知',
        };
    }

    public function textCleaner(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        // 去控制字符、行尾空白、连续空行
        $cleaned = preg_replace(['/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '/[ \t]+$/m', "/\n{3,}/"], ['', '', "\n\n"], $text) ?? $text;

        return ['ok' => true, 'data' => ['清理后行数' => count(explode("\n", trim($cleaned)))], 'text' => trim($cleaned)];
    }

    public function duplicateLineRemover(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        $lines = array_map('trim', explode("\n", $text));
        $unique = array_values(array_unique(array_filter($lines, fn ($l) => $l !== '')));

        return ['ok' => true, 'data' => [
            '原始行数' => count($lines),
            '去重后' => count($unique),
            '重复行' => count($lines) - count($unique),
        ], 'text' => implode("\n", $unique)];
    }
}
