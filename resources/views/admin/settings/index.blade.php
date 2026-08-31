@extends('layouts.admin')
@section('title', __('admin.settings_title'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.settings_title') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.settings_desc') }}</p>
</div>

@php
    // 设置页 2.0：左侧竖向分组导航 + 右侧表单面板
    $tabGroups = [
        __('admin.nav_section_overview') => [
            'main' => __('admin.settings_main'),
            'users' => __('admin.settings_users'),
            'content' => __('admin.settings_content'),
            'analytics' => __('admin.settings_analytics'),
            'maps' => __('admin.settings_maps'),
        ],
        __('admin.nav_section_manage') => [
            'theme' => __('admin.settings_theme'),
            'branding' => __('admin.settings_branding'),
            'custom' => __('admin.settings_custom'),
            'custom_images' => __('admin.settings_custom_images'),
            'ads' => __('admin.settings_ads'),
            'cookie_consent' => __('admin.settings_cookie_consent'),
            'socials' => __('admin.settings_socials'),
            'announcements' => __('admin.settings_announcements'),
        ],
        __('admin.nav_section_monetization') => [
            'payment' => __('admin.settings_payment'),
            'payment_gateways' => __('admin.settings_payment_gateways'),
            'business' => __('admin.settings_business'),
            'plan_free' => __('admin.settings_plan_free'),
            'plan_guest' => __('admin.settings_plan_guest'),
            'plan_custom' => __('admin.settings_plan_custom'),
            'affiliate' => __('admin.settings_affiliate'),
        ],
        __('admin.nav_section_data') => [
            'email_notifications' => __('admin.settings_email_notifications'),
            'email_shield' => __('admin.settings_email_shield'),
            'internal_notifications' => __('admin.settings_internal_notifications'),
            'push_notifications' => __('admin.settings_push_notifications'),
            'webhooks' => __('admin.settings_webhooks'),
        ],
        __('admin.nav_section_system') => [
            'smtp' => __('admin.settings_smtp'),
            'sms' => __('admin.settings_sms'),
            'ai' => __('admin.settings_ai'),
            'captcha' => __('admin.settings_captcha'),
            'pwa' => __('admin.settings_pwa'),
            'offload' => __('admin.settings_offload'),
            'image_optimizer' => __('admin.settings_image_optimizer'),
            'dynamic_og_images' => __('admin.settings_dynamic_og_images'),
            'cron' => __('admin.settings_cron'),
            'cache' => __('admin.settings_cache'),
            'health' => __('admin.settings_health'),
            'support' => __('admin.settings_support'),
        ],
    ];
@endphp

<div class="flex flex-col gap-6 lg:flex-row">
    {{-- 左：竖向 tab 导航（sticky） --}}
    <nav class="w-full shrink-0 lg:sticky lg:top-24 lg:w-60" id="settings-tabs">
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto">
            @foreach ($tabGroups as $groupLabel => $tabs)
                <p class="px-4 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-widest text-zinc-400 first:pt-3">{{ $groupLabel }}</p>
                @foreach ($tabs as $tab => $label)
                    <button type="button" onclick="switchTab('{{ $tab }}')"
                        class="settings-tab flex w-full items-center justify-between border-l-2 px-4 py-2 text-left text-sm font-medium transition {{ $loop->parent->first && $loop->first ? 'border-brand-600 bg-brand-50/70 text-brand-700' : 'border-transparent text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' }}"
                        data-tab="{{ $tab }}">
                        <span class="truncate">{{ $label }}</span>
                    </button>
                @endforeach
            @endforeach
        </div>
    </nav>

    {{-- 右：表单面板 --}}
    <div class="min-w-0 flex-1">
        @php
            $currentTab = old('group', request()->query('tab', 'main'));
            // cache/health/support 为只读运维面板：不包保存表单
            $readonlyTabs = ['cache', 'health', 'support'];
        @endphp

        @foreach(['main','users','content','analytics','maps','theme','branding','custom','custom_images','ads','cookie_consent','socials','announcements','payment','payment_gateways','business','plan_free','plan_guest','plan_custom','affiliate','smtp','sms','ai','captcha','email_notifications','email_shield','internal_notifications','webhooks','pwa','push_notifications','offload','image_optimizer','dynamic_og_images','cron','cache','health','support'] as $tab)
        <div class="settings-panel hidden" id="panel-{{ $tab }}">
            @if(in_array($tab, $readonlyTabs, true))
                @include("admin.settings.partials.{$tab}", ['settings' => $settings[$tab] ?? []])
            @else
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf @method('PUT')
                <input type="hidden" name="group" value="{{ $tab }}">

                @include("admin.settings.partials.{$tab}", ['settings' => $settings[$tab] ?? []])

                <div class="sticky bottom-4 flex justify-end rounded-2xl border border-zinc-200/80 bg-white/90 px-5 py-3 shadow-lg shadow-zinc-900/5 backdrop-blur">
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">
                        {{ __('admin.save') }}
                    </button>
                </div>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.settings-tab').forEach(el => {
        el.classList.remove('border-brand-600', 'bg-brand-50/70', 'text-brand-700');
        el.classList.add('border-transparent', 'text-zinc-600');
    });
    document.querySelectorAll('.settings-panel').forEach(el => el.classList.add('hidden'));

    const activeTab = document.querySelector(`[data-tab="${tab}"]`);
    if (activeTab) {
        activeTab.classList.add('border-brand-600', 'bg-brand-50/70', 'text-brand-700');
        activeTab.classList.remove('border-transparent', 'text-zinc-600');
        activeTab.scrollIntoView({ block: 'nearest' });
    }
    const panel = document.getElementById(`panel-${tab}`);
    if (panel) panel.classList.remove('hidden');

    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    history.replaceState({}, '', url);
}

// Initialize based on URL
const urlTab = new URL(window.location).searchParams.get('tab') || 'main';
switchTab(urlTab);
</script>
@endsection
