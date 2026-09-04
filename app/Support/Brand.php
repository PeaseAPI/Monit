<?php

namespace App\Support;

/**
 * 品牌信息统一出口（M23 · 规格书 §15 品牌可控）
 *
 * 关联：
 * - 数据来源：settings 表 branding.* 组（后台 admin/settings → 品牌 tab 维护）
 * - 视图消费：components/brand-logo、parts/brand_head、parts/brand_footer_scripts、themes/default/*
 * - 设置保存：AdminSettings::update('branding')（validation rules 同步维护）
 *
 * 设计原则：所有品牌元素（logo/favicon/站点名/备案号/页脚代码）均从设置读取，
 * 二次开发者无需改动任何视图即可换肤；留空自动回退默认值。
 */
class Brand
{
    /** 默认主色（与 resources/css/app.css @theme 的 brand-600 呼应） */
    public const DEFAULT_PRIMARY = '#4f46e5';

    /**
     * 站点名称：branding.site_name → main.site_title → app.name
     */
    public static function name(): string
    {
        $name = self::trimOrNull((string) Settings::get('branding.site_name', ''));

        if ($name !== null) {
            return $name;
        }

        $title = self::trimOrNull((string) Settings::get('main.site_title', ''));

        return $title ?? config('app.name', 'Monit');
    }

    /**
     * Logo 地址（$dark=true 时优先取深色版本）
     * 兼容旧 custom_images.logo 设置（M16 遗留字段）
     * 全部留空时回退 public/logo.png（默认品牌资产）
     */
    public static function logoUrl(bool $dark = false): ?string
    {
        if ($dark) {
            $url = self::trimOrNull((string) Settings::get('branding.logo_dark_url', ''));
        } else {
            $url = null;
        }

        $url ??= self::trimOrNull((string) Settings::get('branding.logo_url', ''));
        $url ??= self::trimOrNull((string) Settings::get('custom_images.logo', ''));

        return $url ?? (file_exists(public_path('logo.png')) ? '/logo.png' : null);
    }

    /**
     * Favicon 地址（留空时回退 public/favicon.ico）
     */
    public static function faviconUrl(): ?string
    {
        return self::trimOrNull((string) Settings::get('branding.favicon_url', ''))
            ?? self::trimOrNull((string) Settings::get('custom_images.favicon', ''))
            ?? (file_exists(public_path('favicon.ico')) ? '/favicon.ico' : null);
    }

    /**
     * 主题色（#RRGGBB），用于运行时生成 Tailwind brand 色阶覆盖
     */
    public static function primaryColor(): string
    {
        $color = self::trimOrNull((string) Settings::get('branding.primary_color', ''));

        return ($color && preg_match('/^#[0-9a-fA-F]{6}$/', $color)) ? $color : self::DEFAULT_PRIMARY;
    }

    /**
     * 运行时 brand 色阶覆盖（注入 :root 的 CSS 变量，Tailwind v4 @theme 变量可被运行时覆盖）
     * 由单一主色按 HSL 亮度阶梯派生 10 档色阶，返回完整 <style> 片段
     */
    public static function colorStyleTag(): string
    {
        if (self::primaryColor() === self::DEFAULT_PRIMARY) {
            return '';
        }

        [$h, $s] = self::hexToHsl(self::primaryColor());

        $lightness = [50 => 0.96, 100 => 0.93, 200 => 0.86, 300 => 0.78, 400 => 0.70,
            500 => 0.62, 600 => 0.55, 700 => 0.47, 800 => 0.40, 900 => 0.33];

        $vars = '';
        foreach ($lightness as $shade => $l) {
            $vars .= '--color-brand-'.$shade.': '.self::hslToHex($h, $s, $l).';'."\n";
        }

        return '<style>'."\n".$vars.'</style>';
    }

    /**
     * 页面标题分隔符（main.title_separator，默认 ·）
     */
    public static function titleSeparator(): string
    {
        $separator = trim((string) Settings::get('main.title_separator', ''));

        return $separator !== '' ? mb_substr($separator, 0, 8) : '·';
    }

    /**
     * ICP 备案号（渲染于落地页/公开页页脚，链接指向工信部备案系统）
     */
    public static function icp(): ?string
    {
        return self::trimOrNull((string) Settings::get('branding.footer_icp', ''));
    }

    /**
     * 页脚自定义 HTML/JS（原样输出，管理员责任字段）
     */
    public static function footerHtml(): ?string
    {
        return self::trimOrNull((string) Settings::get('branding.footer_custom_html', ''));
    }

    /**
     * 落地页主题（模板机制：resources/views/themes/{theme}/index.blade.php）
     */
    public static function landingTheme(): string
    {
        $theme = self::trimOrNull((string) Settings::get('branding.landing_theme', ''));

        return ($theme && preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) ? $theme : 'default';
    }

    /**
     * 落地页是否展示定价区块
     */
    public static function showLandingPlans(): bool
    {
        return Settings::get('branding.show_landing_plans', 'true') !== 'false';
    }

    /**
     * 落地页 Hero 标题覆盖（留空用语言包 landing.hero_title）
     */
    public static function heroTitle(): ?string
    {
        return self::trimOrNull((string) Settings::get('branding.landing_hero_title', ''));
    }

    public static function heroSubtitle(): ?string
    {
        return self::trimOrNull((string) Settings::get('branding.landing_hero_subtitle', ''));
    }

    /* ------------------------------------------------------------------ */
    /* 内部工具 */
    /* ------------------------------------------------------------------ */

    protected static function trimOrNull(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    /**
     * hex → [h(0-360), s(0-1)]，亮度由调用方控制
     *
     * @return array{0: float, 1: float}
     */
    protected static function hexToHsl(string $hex): array
    {
        $r = hexdec(substr($hex, 1, 2)) / 255;
        $g = hexdec(substr($hex, 3, 2)) / 255;
        $b = hexdec(substr($hex, 5, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            return [0.0, 0.0];
        }

        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

        $h = match ($max) {
            $r => 60 * (($g - $b) / $d + ($g < $b ? 6 : 0)),
            $g => 60 * (($b - $r) / $d + 2),
            default => 60 * (($r - $g) / $d + 4),
        };

        return [fmod($h, 360), min($s, 0.24)];
    }

    protected static function hslToHex(float $h, float $s, float $l): string
    {
        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        [$r, $g, $b] = match (true) {
            $h < 60 => [$c, $x, 0.0],
            $h < 120 => [$x, $c, 0.0],
            $h < 180 => [0.0, $c, $x],
            $h < 240 => [0.0, $x, $c],
            $h < 300 => [$x, 0.0, $c],
            default => [$c, 0.0, $x],
        };

        return sprintf('#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255),
        );
    }
}
