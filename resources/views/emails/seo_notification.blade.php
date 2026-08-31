<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, 'PingFang SC', 'Microsoft YaHei', sans-serif; background:#f4f4f5; padding:24px;">
    <div style="max-width:560px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; border:1px solid #e4e4e7;">
        <div style="background:#4f46e5; color:#fff; padding:20px 24px;">
            <h2 style="margin:0; font-size:16px;">{{ \App\Support\Brand::name() }} · SEO 通知</h2>
        </div>
        <div style="padding:24px; color:#18181b; font-size:14px; line-height:1.7;">
            <p style="margin:0 0 8px; font-weight:600;">{{ $title }}</p>
            <p style="margin:0 0 16px; color:#52525b; white-space:pre-line;">{{ $message }}</p>
            @if ($link)
                <a href="{{ $link }}" style="display:inline-block; background:#4f46e5; color:#fff; text-decoration:none; padding:10px 20px; border-radius:10px; font-size:13px;">{{ __('seo.view_report') }}</a>
            @endif
        </div>
        <div style="padding:14px 24px; background:#fafafa; color:#a1a1aa; font-size:12px;">
            {{ \App\Support\Brand::name() }} · {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
