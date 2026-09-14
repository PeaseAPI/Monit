@extends('layouts.admin')
@section('title', __('admin.settings_title'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900">{{ __('admin.settings_title') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.settings_desc') }}</p>
</div>

@php
    // 设置页 3.0：URL 驱动（?tab=xxx），左侧分组导航为链接（可分享/新标签/前进后退），
    // 一次只渲染当前 tab 的表单面板——页面不再一次塞下全部 33 个 panel
    $tabGroups = [
        __('admin.nav_section_overview') => [
            'main' => __('admin.settings_main'),
            'main_features' => __('admin.settings_main_features'),
            'main_display' => __('admin.settings_main_display'),
            'users' => __('admin.settings_users'),
            'content' => __('admin.settings_content'),
            'analytics' => __('admin.settings_analytics'),
            'seo' => __('admin.settings_seo'),
            'tickets' => __('admin.settings_tickets'),
            'maps' => __('admin.settings_maps'),
        ],
        __('admin.nav_section_manage') => [
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
            'internal_notifications' => __('admin.settings_internal_notifications'),
            'webhooks' => __('admin.settings_webhooks'),
        ],
        __('admin.nav_section_system') => [
            'smtp' => __('admin.settings_smtp'),
            'sms' => __('admin.settings_sms'),
            'ai' => __('admin.settings_ai'),
            'captcha' => __('admin.settings_captcha'),
            'offload' => __('admin.settings_offload'),
            'cron' => __('admin.settings_cron'),
            'cache' => __('admin.settings_cache'),
            'health' => __('admin.settings_health'),
            'support' => __('admin.settings_support'),
        ],
    ];

    // 控制器已归一非法 tab → main
    $currentTab = $tab;
    // cache/health/support 为只读运维面板：不包保存表单
    $readonlyTabs = ['cache', 'health', 'support'];
@endphp

<div class="flex flex-col gap-6 lg:flex-row">
    {{-- 左：竖向分组导航（sticky，链接式：可分享/新标签/前进后退） --}}
    <nav class="w-full shrink-0 lg:sticky lg:top-24 lg:w-60" id="settings-tabs">
        <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-sm lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto">
            @foreach ($tabGroups as $groupLabel => $tabs)
                <p class="px-4 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-widest text-zinc-400 first:pt-3">{{ $groupLabel }}</p>
                @foreach ($tabs as $tabKey => $label)
                    <a href="{{ route('admin.settings.index', ['tab' => $tabKey]) }}"
                        @class([
                            'settings-tab flex w-full items-center border-l-2 px-4 py-2 text-left text-sm font-medium transition',
                            'border-brand-600 bg-brand-50/70 text-brand-700' => $tabKey === $currentTab,
                            'border-transparent text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900' => $tabKey !== $currentTab,
                        ])>
                        <span class="truncate">{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </nav>

    {{-- 右：当前 tab 的表单面板（仅渲染一个，页面短而快） --}}
    <div class="min-w-0 flex-1">
        <div class="settings-panel" id="panel-{{ $currentTab }}">
            @if(in_array($currentTab, $readonlyTabs, true))
                @include("admin.settings.partials.{$currentTab}", ['settings' => $settings[$currentTab] ?? []])
            @else
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6" enctype="multipart/form-data">
                @csrf @method('PUT')
                <input type="hidden" name="group" value="{{ $currentTab }}">

                @include("admin.settings.partials.{$currentTab}", ['settings' => $settings[$currentTab] ?? []])

                {{-- 独立保存按钮（用户反馈 #19：弃用 sticky 底部大条，改为表单内常规右对齐按钮） --}}
                <div class="flex justify-end border-t border-zinc-100 pt-5">
                    <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-6 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">
                        {{ __('admin.save') }}
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
