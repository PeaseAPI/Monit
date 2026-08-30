{{-- 品牌头部注入（M23）：favicon + 主色运行时覆盖 + 自定义 HEAD 代码
     关联：App\Support\Brand / AdminSettings custom 组 / custom_images 组
     用法：各布局 <head> 内 @vite 之后 @include('parts.brand_head') --}}
@php($favicon = \App\Support\Brand::faviconUrl())
@if ($favicon)
    <link rel="icon" href="{{ $favicon }}">
@else
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
@endif
{!! \App\Support\Brand::colorStyleTag() !!}
@if ($headCss = \App\Support\Settings::get('custom.custom_head_css'))
    <style>{!! $headCss !!}</style>
@endif
@if ($headJs = \App\Support\Settings::get('custom.custom_head_js'))
    {!! $headJs !!}
@endif
