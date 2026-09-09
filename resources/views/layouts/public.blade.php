{{-- 公开内容页布局（SEO 工具中心 / 审计目录等宽内容页）
    与 layouts.guest（auth 表单品牌分栏）区分：本布局提供顶部导航，
    访客可自由往返首页/工具/目录，登录用户显示仪表盘入口 --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('seo.tools_title')) {{ \App\Support\Brand::titleSeparator() }} {{ \App\Support\Brand::name() }}</title>
    @include('parts.brand_head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 font-sans text-zinc-900 antialiased">
    @include('parts.announcement_bar')

    {{-- 全站统一顶部导航（组件：博客/帮助/定价/SEO 页与首页完全一致） --}}
    <x-site-header/>

    {{-- 内容栏默认限宽居中；视图可用 @section('main_class') 覆盖（如 tools 页 hero 全宽） --}}
    <main class="@yield('main_class', 'mx-auto w-full max-w-7xl px-6 py-10')">
        @yield('content')
    </main>

    {{-- 全站统一页脚（组件：深色多栏 + 版权 + ICP） --}}
    <x-site-footer/>

    @include('parts.cookie_consent')
    @include('parts.brand_footer_scripts')
</body>
</html>
