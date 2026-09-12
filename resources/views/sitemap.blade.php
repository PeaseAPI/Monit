<?php /* 本文件禁止出现连写的 PHP 短开标签形态 XML 声明：short_open_tag=On 时会被
   PHP/Laravel Blade 的 token 化机制误判为开标签（线上 500 根因），因此用字符串拼接输出 */ ?>
{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($urls as $u)
    <url>
        <loc>{{ $u['loc'] }}</loc>
@if(!empty($u['lastmod']))
        <lastmod>{{ $u['lastmod'] }}</lastmod>
@endif
@if(!empty($u['changefreq']))
        <changefreq>{{ $u['changefreq'] }}</changefreq>
@endif
        <priority>{{ $u['priority'] }}</priority>
    </url>
@endforeach
</urlset>
