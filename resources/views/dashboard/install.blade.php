@extends('layouts.app', ['nav' => 'dashboard'])

@section('title', __('dashboard.install_code'))

@section('content')
    @php
        $pixelUrl = url('/assets/pixel/monit.js');
        $mode = $website->isLightweight() ? 'lightweight' : 'advanced';
        $snippet = '<script src="' . $pixelUrl . '" data-website-id="' . $website->pixel_key . '" data-mode="' . $mode . '" async></script>';

        // SPA / 手动初始化示例（monit.js 真实 API：自动 hook pushState/replaceState/popstate/hashchange，暴露 MonitPixel.init）
        $spaExample = <<<HTML
<!-- SPA 应用（React / Vue / Next.js 等）：完整模式下路由切换自动追踪，无需额外代码 -->
<script src="{$pixelUrl}" data-website-id="{$website->pixel_key}" data-mode="{$mode}" async></script>

<!-- 需要用户同意（Cookie Consent）后再开始统计：加 data-manual，同意后手动启动 -->
<script src="{$pixelUrl}" data-website-id="{$website->pixel_key}" data-mode="{$mode}" data-manual async></script>
<script>
  document.getElementById('consent-btn').addEventListener('click', function () {
    window.MonitPixel.init(); // 用户同意后再启动统计
  });
</script>
HTML;

        // 自定义事件 / 目标转化示例（monit.js 真实 API：window.monitGoal）
        $goalExample = <<<HTML
<!-- 自定义事件：在后台「统计 → 目标」创建目标 key 后，在关键动作处触发转化 -->
<script>
  // 例：注册表单提交成功后记录 signup 目标
  document.getElementById('signup-form').addEventListener('submit', function () {
    window.monitGoal('signup');
  });

  // 异步场景（如支付完成回调）同样适用
  fetch('/api/checkout', { method: 'POST' }).then(function (res) {
    if (res.ok) window.monitGoal('purchase');
  });
</script>
HTML;
    @endphp

    <div class="mx-auto max-w-3xl">
        <h2 class="text-2xl font-bold">{{ __('dashboard.install_tracking_code') }}</h2>
        <p class="mt-2 text-sm text-zinc-500">
            {{ __('dashboard.install_instructions', ['host' => $website->host]) }}
        </p>

        {{-- Mode info --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border {{ $website->isLightweight() ? 'border-amber-300 bg-amber-50' : 'border-zinc-200 bg-white' }} p-5 shadow-sm">
                <p class="text-sm font-semibold {{ $website->isLightweight() ? 'text-amber-700' : 'text-zinc-700' }}">
                    {{ __('dashboard.current_mode') }}: {{ $website->isLightweight() ? __('websites.lightweight_mode_label') : __('websites.advanced_mode_label') }}
                </p>
                <p class="mt-2 text-sm text-zinc-600">
                    @if ($website->isLightweight())
                        {{ __('websites.lightweight_mode_desc') }}
                    @else
                        {{ __('websites.advanced_mode_desc') }}
                    @endif
                    {{ __('dashboard.switch_in_website_mgmt') }}
                </p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-zinc-700">{{ __('dashboard.pixel_key') }}</p>
                <code class="mt-2 block truncate rounded-lg bg-zinc-950 px-3 py-2 text-sm text-emerald-400">{{ $website->pixel_key }}</code>
                <p class="mt-2 text-xs text-zinc-400">{{ __('dashboard.pixel_key_hint') }}</p>
            </div>
        </div>

        {{-- Install code --}}
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-700">{{ __('dashboard.embed_code') }}</h3>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('snippet').textContent).then(()=>{this.textContent='{{ __('dashboard.copied') }}';setTimeout(()=>this.textContent='{{ __('dashboard.copy_code') }}',1500)})"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">
                    {{ __('dashboard.copy_code') }}
                </button>
            </div>
            <pre id="snippet" class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm leading-relaxed text-zinc-100"><code>{{ $snippet }}</code></pre>
        </div>

        {{-- Advanced --}}
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-zinc-700">{{ __('dashboard.advanced_usage') }}</h3>

            <p class="mt-3 text-sm leading-6 text-zinc-600">{{ __('dashboard.advanced_spa_desc') }}</p>
            <pre class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-xs leading-relaxed text-zinc-300"><code>{{ $spaExample }}</code></pre>

            <p class="mt-5 text-sm leading-6 text-zinc-600">{{ __('dashboard.advanced_goal_desc') }}</p>
            <pre class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-xs leading-relaxed text-zinc-300"><code>{{ $goalExample }}</code></pre>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('dashboard', ['website_id' => $website->website_id]) }}"
               class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('dashboard.back_to_dashboard') }}
            </a>
            <a href="{{ route('websites.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-700">
                {{ __('admin.website_list') }}
            </a>
        </div>
    </div>
@endsection
