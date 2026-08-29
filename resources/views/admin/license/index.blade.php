@extends('layouts.admin')

@section('title', __('admin.license_title'))

@section('content')
<div class="p-8 max-w-3xl">
    <h1 class="text-2xl font-bold text-zinc-100">{{ __('admin.license_title') }}</h1>
    <p class="mt-2 text-sm text-zinc-400">{{ __('admin.license_desc') }}</p>

    {{-- 状态卡 --}}
    <div class="mt-6 rounded-2xl border {{ $status['valid'] ? 'border-green-500/30 bg-green-500/10' : 'border-amber-500/30 bg-amber-500/10' }} p-6">
        <div class="flex items-center gap-3">
            <span class="h-2.5 w-2.5 rounded-full {{ $status['valid'] ? 'bg-green-400' : 'bg-amber-400' }}"></span>
            <span class="text-lg font-semibold {{ $status['valid'] ? 'text-green-300' : 'text-amber-300' }}">
                {{ $status['valid'] ? __('admin.license_valid') : __('admin.license_invalid_' . $status['reason']) }}
            </span>
        </div>
        <p class="mt-2 text-xs text-zinc-400">{{ __('admin.license_reason') }}: <code>{{ $status['reason'] }}</code>　{{ __('admin.license_file') }}: <code>{{ $licensePath }}</code></p>

        @if($license)
        <div class="mt-4 grid gap-3 sm:grid-cols-2 text-sm">
            <div><span class="text-zinc-400">{{ __('admin.license_id') }}</span><br><code class="text-zinc-200">{{ $license['license_id'] ?? '-' }}</code></div>
            <div><span class="text-zinc-400">{{ __('admin.license_product') }}</span><br><code class="text-zinc-200">{{ $license['product'] ?? '-' }}</code></div>
            <div><span class="text-zinc-400">{{ __('admin.license_domains') }}</span><br><code class="text-zinc-200">{{ implode(', ', $license['domains'] ?? []) }}</code><br><span class="text-xs text-zinc-500">{{ __('admin.license_current_host') }}: {{ $currentHost }}</span></div>
            <div><span class="text-zinc-400">{{ __('admin.license_expires') }}</span><br><code class="text-zinc-200">{{ $license['expires'] ?: __('admin.license_permanent') }}</code></div>
            @if(!empty($license['features']))
            <div class="sm:col-span-2"><span class="text-zinc-400">{{ __('admin.license_features') }}</span><br><code class="break-all text-zinc-200">{{ json_encode($license['features'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></div>
            @endif
        </div>
        @endif

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <form method="POST" action="{{ route('admin.license.upload') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                @csrf
                <input type="file" name="license_file" accept=".json,application/json" required class="block w-56 text-sm text-zinc-400 file:mr-3 file:rounded-lg file:border-0 file:bg-zinc-800 file:px-3 file:py-1.5 file:text-xs file:text-zinc-200">
                <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ __('admin.license_upload') }}</button>
            </form>
            <a href="{{ route('admin.license.refresh') }}" class="text-sm text-zinc-400 hover:text-zinc-200">{{ __('admin.license_recheck') }}</a>
        </div>
    </div>

    {{-- 说明 --}}
    <div class="mt-6 rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 text-sm text-zinc-400 leading-relaxed">
        <p class="font-medium text-zinc-200">{{ __('admin.license_howto_title') }}</p>
        <p class="mt-2">{{ __('admin.license_howto_body') }}</p>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-black/40 p-4 text-xs text-zinc-300">php artisan monit:license-generate --domains={{ $currentHost }} --expires=2027-12-31
# 公钥写入 config/monit.php -> license.public_key 或环境变量 MONIT_LICENSE_PUBLIC_KEY</pre>
    </div>
</div>
@endsection
