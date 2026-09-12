<?php /* short_open_tag=On 的环境下 PHP 会把裸 <?xml 行当 PHP 开标签解析（线上 500 根因），必须经 echo 输出 */ ?>
{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
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
