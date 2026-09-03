{{-- 品牌头部注入：favicon + SEO meta + 主色覆盖 + 自定义 HEAD 代码（App\Support\Brand / main 组） --}}
@php($favicon = \App\Support\Brand::faviconUrl())
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@else
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
@endif
@php($siteDescription = trim((string) \App\Support\Settings::get('main.site_description', '')))
{{-- 页面级 SEO 覆盖：@section('meta_description') 优先于全局 main.site_description
     （获客落地页等关键页面需自带精准文案，不依赖站长是否配置全局描述） --}}
@php($pageDescription = trim((string) view()->getSection('meta_description')))
@php($description = $pageDescription !== '' ? $pageDescription : $siteDescription)
@if ($description)
    <meta name="description" content="{{ $description }}">
@endif
{{-- 页面级 canonical：@section('canonical') 声明规范 URL（避免带查询参数的重复内容） --}}
@php($canonical = trim((string) view()->getSection('canonical')))
@if ($canonical)
    <link rel="canonical" href="{{ $canonical }}">
@endif
@php($seoEnabled = \App\Support\Settings::get('main.seo_is_enabled'))
@if ($seoEnabled !== null && ! in_array($seoEnabled, [true, 1, '1', 'true', 'on'], true))
    <meta name="robots" content="noindex, nofollow">
@endif
@php($sitemapUrl = trim((string) \App\Support\Settings::get('main.sitemap_url', '')))
@if ($sitemapUrl)
    <link rel="sitemap" type="application/xml" href="{{ $sitemapUrl }}">
@endif
{{-- Open Graph / Twitter 卡片：社交分享预览。
     og:title 与 <title>（layouts/public L10）同源表达式：页面 @section('title')
     → 全局默认 seo.tools_title → 站名后缀；og:url 复用 canonical，
     og:description 复用 description 覆盖链，避免第三套文案来源 --}}
<meta property="og:site_name" content="{{ \App\Support\Brand::name() }}">
@php($pageTitle = trim((string) view()->getSection('title')))
<meta property="og:title" content="{{ $pageTitle !== '' ? $pageTitle.' '.\App\Support\Brand::titleSeparator().' '.\App\Support\Brand::name() : \App\Support\Brand::name() }}">
<meta property="og:type" content="website">
@if ($canonical)
    <meta property="og:url" content="{{ $canonical }}">
@endif
@if ($description)
    <meta property="og:description" content="{{ $description }}">
@endif
@php($ogImage = trim((string) \App\Support\Settings::get('custom_images.og_image', '')))
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
@else
    <meta name="twitter:card" content="summary">
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

