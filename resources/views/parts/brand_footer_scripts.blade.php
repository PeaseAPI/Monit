{{-- 品牌页脚注入（M23）：自定义 FOOTER JS + 页脚自定义 HTML（备案代码/统计脚本等）
     关联：App\Support\Brand / AdminSettings custom + branding 组
     用法：各布局 </body> 前 @include('parts.brand_footer_scripts') --}}
@if ($footerHtml = \App\Support\Brand::footerHtml())
    {!! $footerHtml !!}
@endif
@if ($adsEnabled = filter_var(\App\Support\Settings::get('ads.ads_is_enabled', false), FILTER_VALIDATE_BOOLEAN))
    {{-- 广告位（原版 ads）：页头代码注入于各布局顶部锚点，页脚在此输出 --}}
    @if ($adsFooter = \App\Support\Settings::get('ads.ads_footer'))
        {!! $adsFooter !!}
    @endif
@endif
@if ($footerJs = \App\Support\Settings::get('custom.custom_footer_js'))
    {!! $footerJs !!}
@endif
