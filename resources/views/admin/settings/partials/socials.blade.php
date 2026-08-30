{{-- 社交登录设置（规格书 §12.3：8 海外 + 5 国内提供商） --}}
@php
    $overseas = ['google','github','facebook','discord','linkedin','microsoft','apple','twitter'];
    $chinese = ['qq'=>'QQ','wechat'=>'微信','weibo'=>'微博','gitee'=>'Gitee','feishu'=>'飞书'];
    $socialSettings = [];
    foreach (array_merge($overseas, array_keys($chinese)) as $provider) {
        $raw = $settings['socials.'.$provider] ?? null;
        $socialSettings[$provider] = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;
    }
@endphp
<div class="space-y-8">
    <h3 class="text-lg font-semibold text-zinc-900">{{ __('admin.settings_socials_overseas') }}</h3>

    @foreach($overseas as $provider)
    <div class="rounded-xl border border-zinc-200 p-5">
        <h4 class="font-medium text-zinc-900 capitalize">{{ $provider }}</h4>
        <div class="mt-3 grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-zinc-600">{{ __('admin.client_id') }}</label>
                <input type="text" name="{{ $provider }}[client_id]" value="{{ old($provider.'.client_id', $socialSettings[$provider]['client_id'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-zinc-600">{{ __('admin.client_secret') }}</label>
                <input type="password" name="{{ $provider }}[client_secret]" value="{{ old($provider.'.client_secret', $socialSettings[$provider]['client_secret'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            </div>
        </div>
        <label class="mt-3 flex items-center gap-2 text-sm">
            <input type="checkbox" name="{{ $provider }}[is_enabled]" value="1" {{ !empty($socialSettings[$provider]['is_enabled']) ? 'checked' : '' }}>
            {{ __('admin.enabled') }}
        </label>
    </div>
    @endforeach

    <h3 class="text-lg font-semibold text-zinc-900 pt-4">{{ __('admin.settings_socials_chinese') }}</h3>

    @foreach($chinese as $key => $label)
    <div class="rounded-xl border border-zinc-200 p-5">
        <h4 class="font-medium text-zinc-900">{{ $label }}</h4>
        <div class="mt-3 grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm text-zinc-600">{{ __('admin.app_id') }}</label>
                <input type="text" name="{{ $key }}[app_id]" value="{{ old($key.'.app_id', $socialSettings[$key]['app_id'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-zinc-600">{{ __('admin.app_secret') }}</label>
                <input type="password" name="{{ $key }}[app_secret]" value="{{ old($key.'.app_secret', $socialSettings[$key]['app_secret'] ?? '') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
            </div>
        </div>
        <label class="mt-3 flex items-center gap-2 text-sm">
            <input type="checkbox" name="{{ $key }}[is_enabled]" value="1" {{ !empty($socialSettings[$key]['is_enabled']) ? 'checked' : '' }}>
            {{ __('admin.enabled') }}
        </label>
    </div>
    @endforeach
</div>
