<?php

namespace App\Services\Seo;

use DOMDocument;
use DOMNodeList;

/**
 * 审计页面上下文：一次抓取，全部测试共享
 */
class AuditContext
{
    public function __construct(
        public readonly string $url,
        public readonly string $scheme,
        public readonly string $host,
        public readonly string $html,
        public readonly array $headers,
        public readonly int $statusCode,
        public readonly int $responseTimeMs,
        public readonly int $sizeBytes,
        public readonly ?string $robotsTxt = null,
        public readonly ?array $sslInfo = null,
    ) {}

    protected ?DOMDocument $dom = null;

    /** 引擎预取的附加数据（robots 状态 / sitemap 状态等） */
    public array $extra = [];

    /**
     * DOM 解析（libxml 容错 + 正则兜底）
     */
    public function dom(): DOMDocument
    {
        if ($this->dom !== null) {
            return $this->dom;
        }

        $dom = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$this->html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $this->dom = $dom;
    }

    /**
     * 提取第一个 meta 标签 content
     */
    public function meta(string $name): ?string
    {
        foreach (['name', 'property', 'http-equiv'] as $attr) {
            $list = $this->dom()->getElementsByTagName('meta');

            foreach ($list as $node) {
                if (strcasecmp((string) $node->getAttribute($attr), $name) === 0) {
                    return $node->getAttribute('content') ?: null;
                }
            }
        }

        return null;
    }

    /**
     * 提取指定标签文本列表
     */
    public function tags(string $tag): DOMNodeList
    {
        return $this->dom()->getElementsByTagName($tag);
    }

    /**
     * 页面可见正文（去 script/style/tag）
     */
    public function bodyText(): string
    {
        $text = preg_replace('#<(script|style|noscript)[^>]*>.*?</\1>#is', ' ', $this->html) ?? '';
        $text = strip_tags($text);

        return trim(preg_replace('/\s+/u', ' ', html_entity_decode($text, ENT_QUOTES, 'UTF-8')) ?? '');
    }

    /**
     * 文本与 HTML 体积比（%）
     */
    public function textRatio(): float
    {
        if ($this->sizeBytes === 0) {
            return 0.0;
        }

        return round(mb_strlen($this->bodyText()) / max(1, strlen($this->html)) * 100, 2);
    }

    /**
     * 头部字段（大小写不敏感）
     */
    public function header(string $name): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strcasecmp((string) $key, $name) === 0) {
                return is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return null;
    }

    /**
     * HTTPS 站点正文中的 http:// 不安全资源（混合内容）
     */
    public function insecureResourceCount(): int
    {
        if ($this->scheme !== 'https') {
            return 0;
        }

        preg_match_all('/(?:src|href)=["\']http:\/\/[^"\']+["\']/i', $this->html, $matches);

        return count($matches[0] ?? []);
    }
}
