{{-- 支持与授权（support）：只读面板——产品版本与 License 状态（原系统 support/license 组） --}}
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.support.desc') }}</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.support.product_version') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">v{{ $settings['version'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.support.license_status') }}</dt>
            <dd class="text-sm font-medium {{ !empty($settings['license_valid']) ? 'text-green-600' : 'text-red-600' }}">
                                {{ !empty($settings['license_valid']) ? __('settings.support.licensed') : __('settings.support.unlicensed') . '（' . ($settings['license_reason'] ?? 'unknown') . '）' }}
            </dd>
        </div>
        @if(!empty($settings['license_data']))
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.support.license_info') }}</dt>
            <dd class="font-mono text-xs text-zinc-500">{{ $settings['license_data']->license ?? $settings['license_data']['license'] ?? '-' }}</dd>
        </div>
        @endif
    </dl>

    <a href="{{ route('admin.license.index') }}"
        class="inline-flex rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                {{ __('settings.support.manage_license') }}
    </a>
</div>
