{{-- 社交登录设置（规格书 §12.3：8 海外 + 5 国内提供商） --}}
{{-- 提供商元数据（用户反馈 #18：每个提供商展示简介 + 获取 Key 的申请地址 + 回调地址） --}}
@php
    $overseas = ['google','github','facebook','discord','linkedin','microsoft','apple','twitter'];
    $chinese = ['qq'=>'QQ','wechat'=>__('settings.socials.wechat'),'weibo'=>__('settings.socials.weibo'),'gitee'=>'Gitee','feishu'=>__('settings.socials.feishu')];
    $providerMeta = [
        'google'    => ['url' => 'https://console.cloud.google.com/apis/credentials'],
        'github'    => ['url' => 'https://github.com/settings/developers'],
        'facebook'  => ['url' => 'https://developers.facebook.com/apps'],
        'discord'   => ['url' => 'https://discord.com/developers/applications'],
        'linkedin'  => ['url' => 'https://www.linkedin.com/developers/apps'],
        'microsoft' => ['url' => 'https://portal.azure.com/#view/Microsoft_AAD_RegisteredApps/ApplicationsListBlade'],
        'apple'     => ['url' => 'https://developer.apple.com/account/resources/identifiers/list/serviceId'],
        'twitter'   => ['url' => 'https://developer.twitter.com/en/portal/projects-and-apps'],
        'qq'        => ['url' => 'https://connect.qq.com/manage.html'],
        'wechat'    => ['url' => 'https://open.weixin.qq.com/'],
        'weibo'     => ['url' => 'https://open.weibo.com/apps'],
        'gitee'     => ['url' => 'https://gitee.com/oauth/applications'],
        'feishu'    => ['url' => 'https://open.feishu.cn/app'],
    ];
    $socialSettings = [];
    foreach (array_merge($overseas, array_keys($chinese)) as $provider) {
        $raw = $settings['socials.'.$provider] ?? null;
        $socialSettings[$provider] = is_string($raw) ? (json_decode($raw, true) ?? []) : (array) $raw;
    }

    // 提供商信息块（简介 + 申请入口 + 回调地址）
    $providerIntro = function (string $provider) use ($providerMeta, $chinese): string {
        $label = $chinese[$provider] ?? ucfirst($provider);

        return '<div class="mt-2 rounded-lg bg-zinc-50 px-3 py-2.5 text-xs text-zinc-500 leading-relaxed">'
            .'<p>'.__('admin.socials_provider_desc', ['provider' => $label]).'</p>'
            .'<p class="mt-1.5 flex flex-wrap items-center gap-x-4 gap-y-1">'
            .'<a href="'.e($providerMeta[$provider]['url'] ?? '#').'" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">'.__('admin.socials_get_key').' &rarr;</a>'
            .'<span>'.__('admin.socials_callback_url').': <code class="rounded bg-white px-1.5 py-0.5 text-[11px] text-zinc-600 select-all">'.e(route('social-login.callback', $provider)).'</code></span>'
            .'</p></div>';
    };
@endphp
<div class="space-y-8">
    <h3 class="text-lg font-semibold text-zinc-900">{{ __('admin.settings_socials_overseas') }}</h3>

    @foreach($overseas as $provider)
    <div class="rounded-xl border border-zinc-200 p-5">
        <h4 class="font-medium text-zinc-900 capitalize">{{ $provider }}</h4>
        {!! $providerIntro($provider) !!}
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
        {!! $providerIntro($key) !!}
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
