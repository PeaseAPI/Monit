@extends('layouts.admin')

@section('title', __('admin.plugins_title'))

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.plugins_title') }}</h1>
            <p class="mt-2 text-sm text-zinc-500">{{ __('admin.plugins_desc') }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="text-zinc-500">{{ __('admin.plugins_total') }}: <span class="font-semibold text-zinc-900">{{ count($plugins) }}</span></p>
            <p class="text-zinc-500">{{ __('admin.plugins_active') }}: <span class="font-semibold text-emerald-600">{{ $totalActive }}</span></p>
        </div>
    </div>

    @if(count($plugins) === 0)
        <div class="mt-8 rounded-2xl border border-zinc-200 bg-white p-8 text-center text-sm text-zinc-500">
            {{ __('admin.plugins_empty') }}
        </div>
    @endif

    <div class="mt-6 space-y-4">
        @foreach($plugins as $plugin)
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-semibold text-zinc-900">{{ $plugin['title'] }}</h3>
                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">v{{ $plugin['version'] }}</span>
                        @if($plugin['active'])
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('admin.plugin_active') }}</span>
                        @elseif($plugin['installed'])
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600">{{ __('admin.plugin_installed') }}</span>
                        @else
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-400">{{ __('admin.plugin_not_installed') }}</span>
                        @endif
                    </div>
                    <p class="mt-1.5 text-sm text-zinc-500">{{ $plugin['description'] }}</p>
                    <p class="mt-1 text-xs text-zinc-500">{{ __('admin.plugin_author') }}: {{ $plugin['author'] }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if(! $plugin['installed'])
                        <form method="POST" action="{{ route('admin.plugins.install', $plugin['id']) }}">@csrf
                            <button class="rounded-xl bg-brand-600 px-4 py-2 text-xs font-medium text-white hover:bg-brand-700">{{ __('admin.plugin_install') }}</button>
                        </form>
                    @elseif($plugin['active'])
                        <form method="POST" action="{{ route('admin.plugins.deactivate', $plugin['id']) }}">@csrf @method('PUT')
                            <button class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50">{{ __('admin.plugin_deactivate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $plugin['id']) }}" onsubmit="return confirm('{{ __('admin.plugins_confirm_uninstall') }}')">@csrf @method('DELETE')
                            <button class="rounded-xl bg-red-50 px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-100">{{ __('admin.plugin_uninstall') }}</button>
                        </form>
                        {{-- 插件专属管理入口 --}}
                        @if($plugin['id'] === 'push-notifications')
                            <a href="{{ route('admin.plugins.push-notifications.campaigns') }}" class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50">Campaign 管理 →</a>
                        @elseif($plugin['id'] === 'image-optimizer')
                            <a href="{{ route('admin.plugins.image-optimizer.stats') }}" class="rounded-xl border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50">压缩统计 →</a>
                        @endif
                    @else
                        <form method="POST" action="{{ route('admin.plugins.activate', $plugin['id']) }}">@csrf
                            <button class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-medium text-white hover:bg-emerald-700">{{ __('admin.plugin_activate') }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.plugins.uninstall', $plugin['id']) }}" onsubmit="return confirm('{{ __('admin.plugins_confirm_uninstall') }}')">@csrf @method('DELETE')
                            <button class="rounded-xl bg-red-50 px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-100">{{ __('admin.plugin_uninstall') }}</button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- 设置表单（已安装且有可配置项时显示）--}}
            @if($plugin['installed'] && ! empty($plugin['settings']))
            <div class="mt-4 border-t border-zinc-100 pt-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('admin.plugin_settings') }}</p>
                <form method="POST" action="{{ route('admin.plugins.settings', $plugin['id']) }}" class="flex flex-wrap items-end gap-3">@csrf
                    @foreach($plugin['settings'] as $key => $definition)
                        <div class="min-w-[180px]">
                            @if(($definition['type'] ?? 'text') === 'bool')
                                <label class="flex items-center gap-2 text-sm text-zinc-700">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input type="checkbox" name="{{ $key }}" value="1" @checked((bool)($plugin['row_settings'][$key] ?? $definition['default'] ?? false)) class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500">
                                    {{ $definition['label'] ?? $key }}
                                </label>
                            @else
                                <label class="block text-xs text-zinc-500">{{ $definition['label'] ?? $key }}</label>
                                <input type="text" name="{{ $key }}" value="{{ $plugin['row_settings'][$key] ?? $definition['default'] ?? '' }}" class="mt-1 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-900">
                            @endif
                        </div>
                    @endforeach
                    <button class="rounded-xl bg-brand-600 px-4 py-2 text-xs font-medium text-white hover:bg-brand-700">{{ __('admin.plugin_settings') }}</button>
                </form>
            </div>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endsection
