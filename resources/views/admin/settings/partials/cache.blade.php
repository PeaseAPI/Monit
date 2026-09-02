{{-- 缓存（cache）：只读运维面板——缓存驱动状态 + 清空缓存操作（原系统 cache 页） --}}
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.cache.desc') }}</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.cache.driver') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.cache.settings_cache') }}</dt>
            <dd class="text-sm font-medium {{ !empty($settings['settings_cached']) ? 'text-green-600' : 'text-zinc-500' }}">
                                {{ !empty($settings['settings_cached']) ? __('settings.cache.cached') : __('settings.cache.not_cached') }}
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.cache.settings_ttl') }}</dt>
                        <dd class="text-sm font-medium text-zinc-900">{{ $settings['settings_ttl_hours'] ?? 12 }} {{ __('settings.cache.hours_auto_invalidate') }}</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.settings.clear_cache') }}" data-confirm="{{ __('settings.cache.confirm_clear') }}">
        @csrf
        <button type="submit"
            class="rounded-xl bg-red-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-700">
            {{ __('settings.cache.clear_btn') }}
        </button>
    </form>
</div>
