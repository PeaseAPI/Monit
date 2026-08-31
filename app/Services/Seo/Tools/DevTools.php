<?php

namespace App\Services\Seo\Tools;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * 开发者实用工具组
 */
class DevTools
{
    public function passwordGenerator(array $in): array
    {
        $length = max(6, min(64, (int) ($in['length'] ?? 16)));

        $sets = ['abcdefghjkmnpqrstuvwxyz', 'ABCDEFGHJKLMNPQRSTUVWXYZ', '23456789', '!@#$%^&*()-_=+'];
        $all = implode('', $sets);

        $password = '';
        foreach ($sets as $set) {
            $password .= $set[random_int(0, strlen($set) - 1)];
        }
        while (strlen($password) < $length) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return ['ok' => true, 'data' => ['密码' => str_shuffle($password), '长度' => $length]];
    }

    /**
     * QR 码：返回生成服务的图片地址
     */
    public function qrGenerator(array $in): array
    {
        $text = trim((string) ($in['text'] ?? ''));

        if ($text === '') {
            return ['ok' => false, 'error' => '请输入内容', 'data' => []];
        }

        $size = max(100, min(1000, (int) ($in['size'] ?? 300)));

        $url = 'https://api.qrserver.com/v1/create-qr-code/?size='.$size.'x'.$size.'&data='.urlencode($text);

        return ['ok' => true, 'data' => ['图片地址' => $url, '尺寸' => $size.'×'.$size]];
    }

    public function userAgentParser(array $in): array
    {
        $ua = trim((string) ($in['ua'] ?? ''));

        if ($ua === '') {
            return ['ok' => false, 'error' => '请输入 User-Agent', 'data' => []];
        }

        $browser = '未知';
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari', 'MSIE' => 'IE'] as $needle => $name) {
            if (str_contains($ua, $needle)) {
                $browser = $name;

                break;
            }
        }

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'macOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => '未知',
        };

        $type = match (true) {
            str_contains($ua, 'bot') || str_contains($ua, 'spider') || str_contains($ua, 'crawl') => '爬虫',
            str_contains($ua, 'Mobile') => '移动端',
            default => '桌面端',
        };

        return ['ok' => true, 'data' => ['浏览器' => $browser, '系统' => $os, '类型' => $type]];
    }

    public function md5Generator(array $in): array
    {
        $text = (string) ($in['text'] ?? '');

        if ($text === '') {
            return ['ok' => false, 'error' => '请输入内容', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            'MD5' => md5($text),
            'SHA-1' => sha1($text),
            'SHA-256' => hash('sha256', $text),
            'CRC32' => hash('crc32b', $text),
        ]];
    }

    public function colorConverter(array $in): array
    {
        $color = trim((string) ($in['color'] ?? ''));

        if (! preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i', $color, $m)) {
            return ['ok' => false, 'error' => '请输入 HEX 颜色值（如 #4f46e5）', 'data' => []];
        }

        $hex = strlen($m[1]) === 3 ? $m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2] : $m[1];

        [$r, $g, $b] = array_map(fn ($v) => (int) hexdec($v), str_split($hex, 2));

        return ['ok' => true, 'data' => [
            'HEX' => '#'.strtoupper($hex),
            'RGB' => "rgb({$r}, {$g}, {$b})",
            'HSL' => sprintf('hsl(%d, %d%%, %d%%)', ...array_values(static::rgbToHsl($r, $g, $b))),
        ]];
    }

    protected static function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0, 0, (int) round($l * 100)];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => ($g - $b) / $d + ($g < $b ? 6 : 0),
            $g => ($b - $r) / $d + 2,
            default => ($r - $g) / $d + 4,
        };

        return [(int) round($h * 60), (int) round($s * 100), (int) round($l * 100)];
    }

    public function utmBuilder(array $in): array
    {
        $url = trim((string) ($in['url'] ?? ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'error' => '请输入有效 URL', 'data' => []];
        }

        $params = array_filter([
            'utm_source' => trim((string) ($in['source'] ?? '')),
            'utm_medium' => trim((string) ($in['medium'] ?? '')),
            'utm_campaign' => trim((string) ($in['campaign'] ?? '')),
            'utm_term' => trim((string) ($in['term'] ?? '')),
            'utm_content' => trim((string) ($in['content'] ?? '')),
        ], fn ($v) => $v !== '');

        if ($params === []) {
            return ['ok' => false, 'error' => '请至少填写一个 UTM 参数', 'data' => []];
        }

        return ['ok' => true, 'data' => ['URL' => $url.(str_contains($url, '?') ? '&' : '?').http_build_query($params)]];
    }

    public function urlParser(array $in): array
    {
        $url = trim((string) ($in['url'] ?? ''));
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return ['ok' => false, 'error' => '请输入有效 URL', 'data' => []];
        }

        return ['ok' => true, 'data' => [
            '协议' => $parts['scheme'] ?? '-',
            '主机' => $parts['host'] ?? '-',
            '端口' => $parts['port'] ?? '-',
            '路径' => $parts['path'] ?? '/',
            '查询参数' => $parts['query'] ?? '-',
            '锚点' => $parts['fragment'] ?? '-',
        ]];
    }

    public function urlConverter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $mode = (string) ($in['mode'] ?? 'encode');

        if ($text === '') {
            return ['ok' => false, 'error' => '请输入内容', 'data' => []];
        }

        return ['ok' => true, 'data' => [], 'text' => $mode === 'encode' ? rawurlencode($text) : rawurldecode($text)];
    }

    public function uuidGenerator(array $in): array
    {
        return ['ok' => true, 'data' => [
            'UUID v4' => (string) Str::uuid(),
            'ULID' => (string) Str::ulid(),
        ]];
    }

    public function numberGenerator(array $in): array
    {
        $min = (int) ($in['min'] ?? 1);
        $max = (int) ($in['max'] ?? 100);
        $count = max(1, min(100, (int) ($in['count'] ?? 1)));

        [$min, $max] = $min <= $max ? [$min, $max] : [$max, $min];

        $numbers = [];
        for ($i = 0; $i < $count; $i++) {
            $numbers[] = random_int($min, $max);
        }

        return ['ok' => true, 'data' => [], 'text' => implode("\n", $numbers)];
    }

    public function base64Converter(array $in): array
    {
        $text = (string) ($in['text'] ?? '');
        $mode = (string) ($in['mode'] ?? 'encode');

        if ($text === '') {
            return ['ok' => false, 'error' => '请输入内容', 'data' => []];
        }

        $result = $mode === 'encode' ? base64_encode($text) : base64_decode($text, true);

        if ($result === false) {
            return ['ok' => false, 'error' => 'Base64 解码失败', 'data' => []];
        }

        return ['ok' => true, 'data' => [], 'text' => $result];
    }

    public function binaryConverter(array $in): array
    {
        $text = trim((string) ($in['text'] ?? ''));
        $mode = (string) ($in['mode'] ?? 'encode');

        if ($text === '') {
            return ['ok' => false, 'error' => '请输入内容', 'data' => []];
        }

        if ($mode === 'encode') {
            $out = '';
            foreach (str_split($text) as $char) {
                $out .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT).' ';
            }

            return ['ok' => true, 'data' => [], 'text' => trim($out)];
        }

        $bits = str_replace(' ', '', $text);

        if (! preg_match('/^[01]+$/', $bits)) {
            return ['ok' => false, 'error' => '仅包含 0/1 的二进制字符串可解码', 'data' => []];
        }

        $out = '';
        foreach (str_split($bits, 8) as $byte) {
            $out .= chr((int) bindec($byte));
        }

        return ['ok' => true, 'data' => [], 'text' => $out];
    }

    /**
     * 明文邮箱检测（页面暴露的 mailto: 链接）
     */
    public function plaintextEmail(array $in): array
    {
        $url = trim((string) ($in['url'] ?? ''));

        try {
            $html = (string) Http::timeout(20)->get($url)->body();
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => mb_substr($e->getMessage(), 0, 200), 'data' => []];
        }

        preg_match_all('/mailto:([\w.+-]+@[\w-]+\.[\w.]+)/i', $html, $matches);

        $emails = array_unique($matches[1] ?? []);

        return ['ok' => true, 'data' => [
            '明文邮箱数' => count($emails),
            '风险' => $emails ? '存在被爬虫收割风险' : '安全',
        ], 'text' => $emails ? implode("\n", $emails) : null];
    }
}
