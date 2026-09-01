{{-- 品牌头部注入：favicon + SEO meta + 主色覆盖 + 自定义 HEAD 代码（App\Support\Brand / main 组） --}}
@php($favicon = \App\Support\Brand::faviconUrl())
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@else
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
@endif
@php($siteDescription = trim((string) \App\Support\Settings::get('main.site_description', '')))
@if ($siteDescription)
    <meta name="description" content="{{ $siteDescription }}">
@endif
@php($seoEnabled = \App\Support\Settings::get('main.seo_is_enabled'))
@if ($seoEnabled !== null && ! in_array($seoEnabled, [true, 1, '1', 'true', 'on'], true))
    <meta name="robots" content="noindex, nofollow">
@endif
@php($ogImage = trim((string) \App\Support\Settings::get('custom_images.og_image', '')))
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
@endif
@php($sitemapUrl = trim((string) \App\Support\Settings::get('main.sitemap_url', '')))
@if ($sitemapUrl)
    <link rel="sitemap" type="application/xml" href="{{ $sitemapUrl }}">
@endif
{!! \App\Support\Brand::colorStyleTag() !!}
@php($headCss = \App\Support\Settings::get('custom.custom_head_css'))
@if ($headCss)
    <style>{!! $headCss !!}</style>
@endif
@php($headJs = \App\Support\Settings::get('custom.custom_head_js'))
@if ($headJs)
    {!! $headJs !!}
@endif

