@extends('layouts.admin')

@section('title', __('admin.plugins_title'))

@section('content')
<div class="max-w-4xl">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.plugins_title') }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ __('admin.plugins_desc') }}</p>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-zinc-200 bg-white px-4 py-2.5 shadow-sm">
            <div class="text-center">
                <p class="text-lg font-bold text-zinc-900">{{ count($plugins) }}</p>
                <p class="text-[11px] text-zinc-400">{{ __('admin.plugins_total') }}</p>
            </div>
            <div class="h-8 w-px bg-zinc-200"></div>
            <div class="text-center">
                <p class="text-lg font-bold text-emerald-600">{{ $totalActive }}</p>
                <p class="text-[11px] text-zinc-400">{{ __('admin.plugins_active') }}</p>
            </div>
        </div>
    </div>

    {{-- 生命周期说明 + 新插件引导（规格书 §14.2 状态机：uninstalled → installed → active） --}}
    <div class="mt-6 space-y-2.5 rounded-2xl border border-brand-200/70 bg-brand-50/40 px-4 py-3.5 text-sm leading-6 text-zinc-600">
        <p class="flex items-start gap-2">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>{{ __('admin.plugins_lifecycle') }}</span>
        </p>
        <p class="flex items-start gap-2">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-brand-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            <span>{{ __('admin.plugins_add_hint') }}</span>
        </p>
    </div>

    @if(count($plugins) === 0)
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-zinc-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
            <p class="mt-3 text-sm text-zinc-500">{{ __('admin.plugins_empty') }}</p>
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @foreach($plugins as $plugin)
        <div class="card p-6 transition-all hover:border-zinc-300 hover:shadow-md">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $plugin['active'] ? 'bg-emerald-50 text-emerald-600' : ($plugin['installed'] ? 'bg-amber-50 text-amber-500' : 'bg-zinc-100 text-zinc-400') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-base font-semibold text-zinc-900">{{ $plugin['title'] }}</h3>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] text-zinc-500">v{{ $plugin['version'] }}</span>
                            @if($plugin['active'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('admin.plugin_active') }}</span>
                            @elseif($plugin['installed'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>{{ __('admin.plugin_installed') }}</span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500"><span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>{{ __('admin.plugin_not_installed') }}</span>
                            @endif
                        </div>
                        <p class="mt-1.5 text-sm leading-6 text-zinc-500">{{ $plugin['description'] }}</p>
                        <p class="mt-1 text-xs text-zinc-400">
                            {{ __('admin.plugin_author') }}: {{ $plugin['author'] }}
                            @if($plugin['url'])
                                · <a href="{{ $plugin['url'] }}" target="_blank" rel="noopener" class="text-zinc-400 underline decoration-zinc-300 underline-offset-2 transition hover:text-brand-600 hover:decoration-brand-400">{{ $plugin['url'] }}</a>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if(! $plugin['installed'])
                        <form method="POST" action="{{ route('admin.plugins.install', $plugin['id']) }}">@csrf
                            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-xs font-medium text-white shadow-sm shadow-brand-600/20 transition hover:bg-brand-700">{{ __('admin.plugin_install') }}</button>
                        </form>
                    @elseif($plugin['active'])
                        <form method="POST" action="{{ route('admin.plugins.deactivate', $plugin['id']) }}">@csrf @method('PUT')
                            <button type="submit" class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">{{ __('admin.plugin_deactivate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $plugin['id']) }}" data-confirm="{{ __('admin.plugins_confirm_uninstall') }}">@csrf @method('DELETE')
                            <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50">{{ __('admin.plugin_uninstall') }}</button>
                        </form>
                        {{-- 插件专属管理入口（必须指向真实注册的路由，渲染死链接会导致插件页 500） --}}
                        @if($plugin['id'] === 'push-notifications')
                            <a href="{{ route('admin.push-notifications.index') }}" class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-50">{{ __('plugins.push.campaign_mgmt_link') }}</a>
                        @endif
                    @else
                        <form method="POST" action="{{ route('admin.plugins.activate', $plugin['id']) }}">@csrf
                            <button type="submit" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-medium text-white shadow-sm shadow-emerald-600/20 transition hover:bg-emerald-700">{{ __('admin.plugin_activate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $plugin['id']) }}" data-confirm="{{ __('admin.plugins_confirm_uninstall') }}">@csrf @method('DELETE')
                            <button type="submit" class="rounded-xl border border-red-200 bg-white px-4 py-2 text-xs font-medium text-red-600 transition hover:bg-red-50">{{ __('admin.plugin_uninstall') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 插件设置（已安装且有可配置项时显示；写入 plugins 表，保存即生效）--}}
            @if($plugin['installed'] && ! empty($plugin['settings']))
            <div class="mt-5 rounded-2xl border border-zinc-100 bg-zinc-50/60 p-5">
                <p class="mb-4 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('admin.plugin_settings') }}</p>
                <form method="POST" action="{{ route('admin.plugins.settings', $plugin['id']) }}">
                    @csrf
                    <div class="settings-field-grid">
                        @foreach($plugin['settings'] as $key => $definition)
                            @if(($definition['type'] ?? 'text') === 'bool')
                                <label class="settings-field-row">
                                    <span class="min-w-0">
                                        <span class="settings-field-row-label">{{ $definition['label'] ?? $key }}</span>
                                    </span>
                                    <span class="flex shrink-0 items-center">
                                        <input type="hidden" name="{{ $key }}" value="0">
                                        <input type="checkbox" name="{{ $key }}" value="1" @checked((bool)($plugin['row_settings'][$key] ?? $definition['default'] ?? false)) class="input-toggle">
                                    </span>
                                </label>
                            @else
                                <div>
                                    <label class="form-label">{{ $definition['label'] ?? $key }}</label>
                                    <input type="text" name="{{ $key }}" value="{{ $plugin['row_settings'][$key] ?? $definition['default'] ?? '' }}" class="form-input">
                                </div>
                            @endif
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-end border-t border-zinc-200/70 pt-4">
                        <button type="submit" class="rounded-xl bg-gradient-to-r from-brand-600 to-brand-700 px-5 py-2 text-xs font-semibold text-white shadow-md shadow-brand-600/20 transition hover:from-brand-700 hover:to-brand-800">{{ __('common.save') }}</button>
                    </div>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
