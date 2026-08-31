@extends('layouts.admin')
@section('title', __('admin.settings_title'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.settings_title') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.settings_desc') }}</p>
</div>

{{-- 选项卡导航 --}}
<div class="mb-6 border-b border-zinc-200">
    <nav class="-mb-px flex space-x-6 overflow-x-auto" id="settings-tabs">
        @foreach([
            'main' => __('admin.settings_main'),
            'users' => __('admin.settings_users'),
            'content' => __('admin.settings_content'),
            'analytics' => __('admin.settings_analytics'),
            'maps' => __('admin.settings_maps'),
            'theme' => __('admin.settings_theme'),
            'branding' => __('admin.settings_branding'),
            'custom' => __('admin.settings_custom'),
            'custom_images' => __('admin.settings_custom_images'),
            'ads' => __('admin.settings_ads'),
            'cookie_consent' => __('admin.settings_cookie_consent'),
            'socials' => __('admin.settings_socials'),
            'announcements' => __('admin.settings_announcements'),
            'payment' => __('admin.settings_payment'),
            'payment_gateways' => __('admin.settings_payment_gateways'),
            'business' => __('admin.settings_business'),
            'plan_free' => __('admin.settings_plan_free'),
            'plan_guest' => __('admin.settings_plan_guest'),
            'plan_custom' => __('admin.settings_plan_custom'),
            'affiliate' => __('admin.settings_affiliate'),
            'smtp' => __('admin.settings_smtp'),
            'sms' => __('admin.settings_sms'),
            'ai' => __('admin.settings_ai'),
            'captcha' => __('admin.settings_captcha'),
            'email_notifications' => __('admin.settings_email_notifications'),
            'email_shield' => __('admin.settings_email_shield'),
            'internal_notifications' => __('admin.settings_internal_notifications'),
            'webhooks' => __('admin.settings_webhooks'),
            'pwa' => __('admin.settings_pwa'),
            'push_notifications' => __('admin.settings_push_notifications'),
            'offload' => __('admin.settings_offload'),
            'image_optimizer' => __('admin.settings_image_optimizer'),
            'dynamic_og_images' => __('admin.settings_dynamic_og_images'),
            'cron' => __('admin.settings_cron'),
            'cache' => __('admin.settings_cache'),
            'health' => __('admin.settings_health'),
            'support' => __('admin.settings_support'),
        ] as $tab => $label)
        <button type="button" onclick="switchTab('{{ $tab }}')"
            class="settings-tab whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium {{ $loop->first ? 'border-brand-600 text-brand-600' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700' }}"
            data-tab="{{ $tab }}">
            {{ $label }}
        </button>
        @endforeach
    </nav>
</div>

{{-- 选项卡内容区域 --}}
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
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-6">
        @csrf @method('PUT')
        <input type="hidden" name="group" value="{{ $tab }}">

        @include("admin.settings.partials.{$tab}", ['settings' => $settings[$tab] ?? []])

        <div class="pt-4">
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                {{ __('admin.save') }}
            </button>
        </div>
    </form>
    @endif
</div>
@endforeach

<script>
function switchTab(tab) {
    document.querySelectorAll('.settings-tab').forEach(el => {
        el.classList.remove('border-brand-600', 'text-brand-600');
        el.classList.add('border-transparent', 'text-zinc-500');
    });
    document.querySelectorAll('.settings-panel').forEach(el => el.classList.add('hidden'));

    const activeTab = document.querySelector(`[data-tab="${tab}"]`);
    if (activeTab) {
        activeTab.classList.add('border-brand-600', 'text-brand-600');
        activeTab.classList.remove('border-transparent', 'text-zinc-500');
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
