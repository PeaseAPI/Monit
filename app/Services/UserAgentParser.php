<?php

namespace App\Services;

/**
 * Monit 用户代理解析器
 * 替代原系统 jaybizzle/crawler-detect + UA 检测（无第三方依赖实现）
 */
class UserAgentParser
{
    /**
     * 常见爬虫 / 机器人 UA 关键字
     */
    protected const CRAWLER_PATTERNS = [
        'bot', 'crawl', 'spider', 'slurp', 'bingpreview', 'yandex', 'baidu',
        'duckduckgo', 'sogou', 'semrush', 'ahrefs', 'mj12', 'dotbot', 'petalbot',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'embedly', 'quora link preview',
        'headlesschrome', 'phantomjs', 'puppeteer', 'playwright', 'selenium',
        'curl/', 'wget', 'python-requests', 'python-urllib', 'go-http-client',
        'java/', 'okhttp', 'apache-httpclient', 'guzzlehttp', 'postmanruntime',
        'monitoring', 'uptime', 'pingdom', 'site24x7', 'statuscake',
    ];

    protected string $userAgent;

    public function __construct(?string $userAgent)
    {
        $this->userAgent = trim((string) $userAgent);
    }

    public static function make(?string $userAgent): static
    {
        return new static($userAgent);
    }

    public function isCrawler(): bool
    {
        $ua = strtolower($this->userAgent);

        if ($ua === '') {
            return false;
        }

        foreach (self::CRAWLER_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 设备类型：desktop / tablet / mobile
     */
    public function deviceType(): string
    {
        $ua = strtolower($this->userAgent);

        // 平板优先（iPad 新版 UA 自称 Mac，通过触摸检测在 SDK 端补充）
        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')
            || (str_contains($ua, 'android') && ! str_contains($ua, 'mobile'))) {
            return 'tablet';
        }

        if (str_contains($ua, 'iphone') || str_contains($ua, 'ipod')
            || str_contains($ua, 'android') || str_contains($ua, 'mobile')
            || str_contains($ua, 'windows phone')) {
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * 操作系统：[name, version]
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function os(): array
    {
        $ua = $this->userAgent;

        $patterns = [
            'Windows' => '/Windows NT ([0-9.]+)/',
            'macOS' => '/Mac OS X ([0-9_.]+)/',
            'Android' => '/Android ([0-9.]+)/',
            'iOS' => '/(?:iPhone|iPad).*?OS ([0-9_]+)/i',
            'Chrome OS' => '/CrOS/',
            'Linux' => '/Linux/',
            'Ubuntu' => '/Ubuntu\/?([0-9.]*)/',
        ];

        $versionMap = [
            '10.0' => '10', '11.0' => '11', '12.0' => '12', '13.0' => '13', '14.0' => '14',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                $version = isset($m[1]) ? str_replace('_', '.', $m[1]) : null;
                if ($name === 'Windows' && $version !== null) {
                    $version = $versionMap[$version] ?? $version;
                }
                if ($name === 'Linux' && $version === '') {
                    $version = null;
                }

                return [$name, $version ?: null];
            }
        }

        return [null, null];
    }

    /**
     * 浏览器：[name, version]
     *
     * @return array{0: ?string, 1: ?string}
     */
    public function browser(): array
    {
        $ua = $this->userAgent;

        // 顺序很重要：先匹配基于 Chromium 的浏览器
        $patterns = [
            'Edg' => '/Edg(?:e|A|iOS)?\/([0-9.]+)/',
            'OPR' => '/(?:OPR|Opera)\/([0-9.]+)/',
            'Samsung Browser' => '/SamsungBrowser\/([0-9.]+)/',
            'MIUI Browser' => '/MiuiBrowser\/([0-9.]+)/',
            'Firefox' => '/(?:Firefox|FxiOS)\/([0-9.]+)/',
            'Chrome' => '/Chrome\/([0-9.]+)/',
            'Safari' => '/Version\/([0-9.]+).*Safari/',
            'Safari' => '/Safari\/([0-9.]+)/',
        ];

        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                return [$name, $m[1] ?? null];
            }
        }

        return [null, null];
    }
}
