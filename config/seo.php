<?php

/*
|--------------------------------------------------------------------------
| SEO 模块配置
|--------------------------------------------------------------------------
| tests：审计测试注册表（55 核心 + 5 外部条件项 = 60）
|   - category：seo / content / performance / security / links / misc / mixed_content
|   - importance：major(3 分) / moderate(2 分) / minor(1 分)，加权计总分
|   - requires：外部凭据设置键，未配置时该测试自动跳过（条件项）
| tools：工具中心注册表，fields 为表单字段（名称 => 类型），handler 为分组处理器方法
*/

return [

    'categories' => [
        'seo' => ['label_key' => 'seo.category_seo'],
        'content' => ['label_key' => 'seo.category_content'],
        'performance' => ['label_key' => 'seo.category_performance'],
        'security' => ['label_key' => 'seo.category_security'],
        'links' => ['label_key' => 'seo.category_links'],
        'misc' => ['label_key' => 'seo.category_misc'],
        'mixed_content' => ['label_key' => 'seo.category_mixed_content'],
    ],

    /*
    |----------------------------------------------------------------------
    | 审计测试注册表
    |----------------------------------------------------------------------
    */
    'tests' => [

        // ---- seo 可索引与元信息（20）----
        'title' => ['category' => 'seo', 'importance' => 'major'],
        'meta_description' => ['category' => 'seo', 'importance' => 'major'],
        'h1' => ['category' => 'seo', 'importance' => 'major'],
        'meta_keywords' => ['category' => 'seo', 'importance' => 'moderate'],
        'other_headings' => ['category' => 'seo', 'importance' => 'minor'],
        'language' => ['category' => 'seo', 'importance' => 'minor'],
        'meta_charset' => ['category' => 'seo', 'importance' => 'minor'],
        'meta_viewport' => ['category' => 'seo', 'importance' => 'moderate'],
        'meta_refresh' => ['category' => 'seo', 'importance' => 'moderate'],
        'canonical' => ['category' => 'seo', 'importance' => 'major'],
        'opengraph' => ['category' => 'seo', 'importance' => 'moderate'],
        'schemas' => ['category' => 'seo', 'importance' => 'moderate'],
        'favicon' => ['category' => 'seo', 'importance' => 'minor'],
        'not_found' => ['category' => 'seo', 'importance' => 'major'],
        'robots' => ['category' => 'seo', 'importance' => 'major'],
        'meta_robots' => ['category' => 'seo', 'importance' => 'major'],
        'header_robots' => ['category' => 'seo', 'importance' => 'moderate'],
        'is_seo_friendly_url' => ['category' => 'seo', 'importance' => 'moderate'],
        'noindex_images' => ['category' => 'seo', 'importance' => 'minor'],
        'gsc_is_indexed' => ['category' => 'seo', 'importance' => 'moderate', 'requires' => 'seo.gsc_is_enabled'],

        // ---- content 内容质量（7）----
        'words_count' => ['category' => 'content', 'importance' => 'moderate'],
        'words_used' => ['category' => 'content', 'importance' => 'minor'],
        'text_to_html_ratio' => ['category' => 'content', 'importance' => 'moderate'],
        'social_links' => ['category' => 'content', 'importance' => 'minor'],
        'emails' => ['category' => 'content', 'importance' => 'minor'],
        'content_keywords' => ['category' => 'content', 'importance' => 'moderate'],
        'image_keywords' => ['category' => 'content', 'importance' => 'minor'],

        // ---- security 安全（10）----
        'is_https' => ['category' => 'security', 'importance' => 'major'],
        'is_ssl_valid' => ['category' => 'security', 'importance' => 'major'],
        'hsts' => ['category' => 'security', 'importance' => 'moderate'],
        'csp' => ['category' => 'security', 'importance' => 'moderate'],
        'unsafe_forms' => ['category' => 'security', 'importance' => 'moderate'],
        'unsafe_external_links' => ['category' => 'security', 'importance' => 'moderate'],
        'safe_browsing' => ['category' => 'security', 'importance' => 'major', 'requires' => 'seo.safe_browsing_is_enabled'],
        'header_server' => ['category' => 'security', 'importance' => 'minor'],
        'spf' => ['category' => 'security', 'importance' => 'minor'],
        'referrer_policy' => ['category' => 'security', 'importance' => 'moderate'],

        // ---- links 链接（3）----
        'internal_links' => ['category' => 'links', 'importance' => 'moderate'],
        'external_links' => ['category' => 'links', 'importance' => 'moderate'],
        'in_page_links' => ['category' => 'links', 'importance' => 'minor'],

        // ---- misc 其它（3）----
        'image_alt' => ['category' => 'misc', 'importance' => 'major'],
        'doctype' => ['category' => 'misc', 'importance' => 'minor'],
        'sitemap' => ['category' => 'misc', 'importance' => 'major'],

        // ---- mixed_content 混合内容（1）----
        'mixed_content' => ['category' => 'mixed_content', 'importance' => 'major'],

        // ---- 外部条件项（requires 对应设置未配置时自动跳过）----
        'gsc_coverage' => ['category' => 'seo', 'importance' => 'moderate', 'requires' => 'seo.gsc_is_enabled'],
        'ahrefs_domain_rating' => ['category' => 'seo', 'importance' => 'moderate', 'requires' => 'seo.ahrefs_api_key'],
        'page_rank' => ['category' => 'seo', 'importance' => 'minor', 'requires' => 'seo.pagerank_api_key'],
        'bing_indexed' => ['category' => 'seo', 'importance' => 'minor', 'requires' => 'seo.bing_api_key'],
        'yandex_indexed' => ['category' => 'seo', 'importance' => 'minor', 'requires' => 'seo.yandex_api_key'],
    ],

    /*
    |----------------------------------------------------------------------
    | 工具中心注册表（字段类型：url / text / textarea / number / select:x,y）
    |----------------------------------------------------------------------
    */
    'tool_categories' => [
        'network' => ['label_key' => 'seo.tool_cat_network'],
        'seo_check' => ['label_key' => 'seo.tool_cat_seo_check'],
        'preview' => ['label_key' => 'seo.tool_cat_preview'],
        'minify' => ['label_key' => 'seo.tool_cat_minify'],
        'text' => ['label_key' => 'seo.tool_cat_text'],
        'dev' => ['label_key' => 'seo.tool_cat_dev'],
    ],

    'tools' => [

        // ---- 网络与域名 ----
        'dns_lookup' => ['category' => 'network', 'handler' => 'dnsLookup', 'fields' => ['domain' => 'text']],
        'ip_lookup' => ['category' => 'network', 'handler' => 'ipLookup', 'fields' => ['ip' => 'text']],
        'ssl_lookup' => ['category' => 'network', 'handler' => 'sslLookup', 'fields' => ['host' => 'text']],
        'whois_lookup' => ['category' => 'network', 'handler' => 'whoisLookup', 'fields' => ['domain' => 'text']],
        'ping' => ['category' => 'network', 'handler' => 'ping', 'fields' => ['host' => 'text']],
        'reverse_ip_lookup' => ['category' => 'network', 'handler' => 'reverseIpLookup', 'fields' => ['ip' => 'text']],
        'domain_ip_lookup' => ['category' => 'network', 'handler' => 'domainIpLookup', 'fields' => ['domain' => 'text']],
        'website_status_checker' => ['category' => 'network', 'handler' => 'statusChecker', 'fields' => ['url' => 'url']],
        'redirect_checker' => ['category' => 'network', 'handler' => 'redirectChecker', 'fields' => ['url' => 'url']],
        'url_redirect_checker' => ['category' => 'network', 'handler' => 'redirectTrace', 'fields' => ['url' => 'url']],
        'ttfb_checker' => ['category' => 'network', 'handler' => 'ttfbChecker', 'fields' => ['url' => 'url']],
        'website_hosting_checker' => ['category' => 'network', 'handler' => 'hostingChecker', 'fields' => ['url' => 'url']],
        'http_headers_lookup' => ['category' => 'network', 'handler' => 'headersLookup', 'fields' => ['url' => 'url']],
        'http2_checker' => ['category' => 'network', 'handler' => 'http2Checker', 'fields' => ['url' => 'url']],
        'brotli_checker' => ['category' => 'network', 'handler' => 'brotliChecker', 'fields' => ['url' => 'url']],
        'google_cache_checker' => ['category' => 'network', 'handler' => 'googleCacheChecker', 'fields' => ['url' => 'url']],
        'idn_converter' => ['category' => 'network', 'handler' => 'idnConverter', 'fields' => ['domain' => 'text']],
        'website_text_extractor' => ['category' => 'network', 'handler' => 'textExtractor', 'fields' => ['url' => 'url']],
        'website_page_size_checker' => ['category' => 'network', 'handler' => 'pageSizeChecker', 'fields' => ['url' => 'url']],

        // ---- SEO 检查 ----
        'meta_tags_checker' => ['category' => 'seo_check', 'handler' => 'metaTags', 'fields' => ['url' => 'url']],
        'keyword_density_checker' => ['category' => 'seo_check', 'handler' => 'keywordDensity', 'fields' => ['url' => 'url', 'min_length' => 'number']],
        'open_graph_checker' => ['category' => 'seo_check', 'handler' => 'openGraph', 'fields' => ['url' => 'url']],
        'twitter_card_checker' => ['category' => 'seo_check', 'handler' => 'twitterCard', 'fields' => ['url' => 'url']],
        'robots_txt_checker' => ['category' => 'seo_check', 'handler' => 'robotsTxt', 'fields' => ['url' => 'url']],
        'sitemap_checker' => ['category' => 'seo_check', 'handler' => 'sitemapChecker', 'fields' => ['url' => 'url']],
        'mixed_content_checker' => ['category' => 'seo_check', 'handler' => 'mixedContent', 'fields' => ['url' => 'url']],
        'safe_url_checker' => ['category' => 'seo_check', 'handler' => 'safeUrl', 'fields' => ['url' => 'url']],
        'favicon_checker' => ['category' => 'seo_check', 'handler' => 'faviconChecker', 'fields' => ['url' => 'url']],
        'h1_checker' => ['category' => 'seo_check', 'handler' => 'h1Checker', 'fields' => ['url' => 'url']],
        'image_alt_checker' => ['category' => 'seo_check', 'handler' => 'imageAlt', 'fields' => ['url' => 'url']],
        'broken_link_checker' => ['category' => 'seo_check', 'handler' => 'brokenLinks', 'fields' => ['url' => 'url']],
        'url_seo_checker' => ['category' => 'seo_check', 'handler' => 'urlSeo', 'fields' => ['url' => 'url']],
        'canonical_checker' => ['category' => 'seo_check', 'handler' => 'canonicalChecker', 'fields' => ['url' => 'url']],
        'hreflang_checker' => ['category' => 'seo_check', 'handler' => 'hreflangChecker', 'fields' => ['url' => 'url']],
        'structured_data_checker' => ['category' => 'seo_check', 'handler' => 'structuredData', 'fields' => ['url' => 'url']],
        'viewport_checker' => ['category' => 'seo_check', 'handler' => 'viewportChecker', 'fields' => ['url' => 'url']],
        'language_checker' => ['category' => 'seo_check', 'handler' => 'languageChecker', 'fields' => ['url' => 'url']],
        'charset_checker' => ['category' => 'seo_check', 'handler' => 'charsetChecker', 'fields' => ['url' => 'url']],
        'text_html_ratio_checker' => ['category' => 'seo_check', 'handler' => 'textHtmlRatio', 'fields' => ['url' => 'url']],
        'cache_headers_checker' => ['category' => 'seo_check', 'handler' => 'cacheHeaders', 'fields' => ['url' => 'url']],
        'security_headers_checker' => ['category' => 'seo_check', 'handler' => 'securityHeaders', 'fields' => ['url' => 'url']],
        'email_extractor' => ['category' => 'seo_check', 'handler' => 'emailExtractor', 'fields' => ['text' => 'textarea']],
        'link_extractor' => ['category' => 'seo_check', 'handler' => 'linkExtractor', 'fields' => ['text' => 'textarea']],
        'image_extractor' => ['category' => 'seo_check', 'handler' => 'imageExtractor', 'fields' => ['text' => 'textarea']],
        'heading_extractor' => ['category' => 'seo_check', 'handler' => 'headingExtractor', 'fields' => ['text' => 'textarea']],
        'keyword_extractor' => ['category' => 'seo_check', 'handler' => 'keywordExtractor', 'fields' => ['text' => 'textarea']],
        'uptime_calculator' => ['category' => 'seo_check', 'handler' => 'uptimeCalculator', 'fields' => ['downtime_minutes' => 'number', 'period_days' => 'number']],
        'readability_checker' => ['category' => 'seo_check', 'handler' => 'readability', 'fields' => ['text' => 'textarea']],
        'meta_length_checker' => ['category' => 'seo_check', 'handler' => 'metaLength', 'fields' => ['title' => 'text', 'description' => 'text']],
        'seo_score_checker' => ['category' => 'seo_check', 'handler' => 'seoScore', 'fields' => ['url' => 'url']],
        'duplicate_content_checker' => ['category' => 'seo_check', 'handler' => 'duplicateContent', 'fields' => ['url_a' => 'url', 'url_b' => 'url']],
        'email_protector' => ['category' => 'seo_check', 'handler' => 'emailProtector', 'fields' => ['email' => 'text']],

        // ---- 搜索预览 ----
        'google_search_preview' => ['category' => 'preview', 'handler' => 'googlePreview', 'fields' => ['title' => 'text', 'url' => 'text', 'description' => 'text']],
        'bing_search_preview' => ['category' => 'preview', 'handler' => 'bingPreview', 'fields' => ['title' => 'text', 'url' => 'text', 'description' => 'text']],
        'yandex_search_preview' => ['category' => 'preview', 'handler' => 'yandexPreview', 'fields' => ['title' => 'text', 'url' => 'text', 'description' => 'text']],
        'yahoo_search_preview' => ['category' => 'preview', 'handler' => 'yahooPreview', 'fields' => ['title' => 'text', 'url' => 'text', 'description' => 'text']],

        // ---- 压缩与格式化 ----
        'html_minifier' => ['category' => 'minify', 'handler' => 'htmlMinifier', 'fields' => ['code' => 'textarea']],
        'css_minifier' => ['category' => 'minify', 'handler' => 'cssMinifier', 'fields' => ['code' => 'textarea']],
        'js_minifier' => ['category' => 'minify', 'handler' => 'jsMinifier', 'fields' => ['code' => 'textarea']],
        'json_validator' => ['category' => 'minify', 'handler' => 'jsonValidator', 'fields' => ['code' => 'textarea']],
        'text_cleaner' => ['category' => 'minify', 'handler' => 'textCleaner', 'fields' => ['text' => 'textarea']],
        'duplicate_line_remover' => ['category' => 'minify', 'handler' => 'duplicateLineRemover', 'fields' => ['text' => 'textarea']],

        // ---- 文本与内容 ----
        'word_counter' => ['category' => 'text', 'handler' => 'wordCounter', 'fields' => ['text' => 'textarea']],
        'char_counter' => ['category' => 'text', 'handler' => 'charCounter', 'fields' => ['text' => 'textarea']],
        'case_converter' => ['category' => 'text', 'handler' => 'caseConverter', 'fields' => ['text' => 'textarea', 'mode' => 'select:upper,lower,title,sentence,camel,snake,kebab']],
        'text_to_slug_converter' => ['category' => 'text', 'handler' => 'slugConverter', 'fields' => ['text' => 'text', 'separator' => 'select:-,_']],
        'text_replacer' => ['category' => 'text', 'handler' => 'textReplacer', 'fields' => ['text' => 'textarea', 'search' => 'text', 'replace' => 'text']],
        'text_reverser' => ['category' => 'text', 'handler' => 'textReverser', 'fields' => ['text' => 'textarea']],
        'lorem_ipsum_generator' => ['category' => 'text', 'handler' => 'loremGenerator', 'fields' => ['paragraphs' => 'number']],
        'reading_time_calculator' => ['category' => 'text', 'handler' => 'readingTime', 'fields' => ['text' => 'textarea', 'wpm' => 'number']],
        'timestamp_converter' => ['category' => 'text', 'handler' => 'timestampConverter', 'fields' => ['value' => 'text']],
        'keyword_density_counter' => ['category' => 'text', 'handler' => 'keywordDensityText', 'fields' => ['text' => 'textarea', 'keyword' => 'text']],

        // ---- 开发者实用 ----
        'password_generator' => ['category' => 'dev', 'handler' => 'passwordGenerator', 'fields' => ['length' => 'number']],
        'qr_generator' => ['category' => 'dev', 'handler' => 'qrGenerator', 'fields' => ['text' => 'text', 'size' => 'number']],
        'user_agent_parser' => ['category' => 'dev', 'handler' => 'userAgentParser', 'fields' => ['ua' => 'text']],
        'md5_generator' => ['category' => 'dev', 'handler' => 'md5Generator', 'fields' => ['text' => 'textarea']],
        'color_converter' => ['category' => 'dev', 'handler' => 'colorConverter', 'fields' => ['color' => 'text']],
        'utm_builder' => ['category' => 'dev', 'handler' => 'utmBuilder', 'fields' => ['url' => 'url', 'source' => 'text', 'medium' => 'text', 'campaign' => 'text', 'term' => 'text', 'content' => 'text']],
        'url_parser' => ['category' => 'dev', 'handler' => 'urlParser', 'fields' => ['url' => 'url']],
        'url_converter' => ['category' => 'dev', 'handler' => 'urlConverter', 'fields' => ['text' => 'textarea', 'mode' => 'select:encode,decode']],
        'uuid_generator' => ['category' => 'dev', 'handler' => 'uuidGenerator', 'fields' => []],
        'number_generator' => ['category' => 'dev', 'handler' => 'numberGenerator', 'fields' => ['min' => 'number', 'max' => 'number', 'count' => 'number']],
        'base64_converter' => ['category' => 'dev', 'handler' => 'base64Converter', 'fields' => ['text' => 'textarea', 'mode' => 'select:encode,decode']],
        'binary_converter' => ['category' => 'dev', 'handler' => 'binaryConverter', 'fields' => ['text' => 'text', 'mode' => 'select:encode,decode']],
        'plaintext_email_checker' => ['category' => 'dev', 'handler' => 'plaintextEmail', 'fields' => ['url' => 'url']],

        // ---- 权重指标（条件工具，配置 API Key 后注册）----
        'ahrefs_domain_rating' => ['category' => 'seo_check', 'handler' => 'ahrefsDomainRating', 'fields' => ['domain' => 'text'], 'requires' => 'seo.ahrefs_api_key'],
    ],

    /*
    |----------------------------------------------------------------------
    | 默认阈值（后台 seo 设置组可覆盖：键名 seo_{key}）
    |----------------------------------------------------------------------
    */
    'thresholds' => [
        'title_min' => 10,
        'title_max' => 60,
        'description_min' => 50,
        'description_max' => 160,
        'words_count_min' => 300,
        'response_time_max' => 1500,
        'page_size_max' => 3000000,
        'dom_size_max' => 1500,
        'http_requests_max' => 50,
        'text_html_ratio_min' => 10,
        'keywords_top' => 10,
    ],
];


