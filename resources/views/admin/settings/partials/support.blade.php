{{-- 支持与授权（support）：只读面板——产品版本与 License 状态（原系统 support/license 组） --}}
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">产品版本与授权状态，全部只读；License 文件请在上传处管理。</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">产品版本</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">v{{ $settings['version'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">授权状态</dt>
            <dd class="text-sm font-medium {{ !empty($settings['license_valid']) ? 'text-green-600' : 'text-red-600' }}">
                {{ !empty($settings['license_valid']) ? '已授权' : '未授权（'.($settings['license_reason'] ?? 'unknown').'）' }}
            </dd>
        </div>
        @if(!empty($settings['license_data']))
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">授权信息</dt>
            <dd class="font-mono text-xs text-zinc-500">{{ $settings['license_data']->license ?? $settings['license_data']['license'] ?? '-' }}</dd>
        </div>
        @endif
    </dl>

    <a href="{{ route('admin.license.index') }}"
        class="inline-flex rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
        前往授权许可管理
    </a>
</div>
