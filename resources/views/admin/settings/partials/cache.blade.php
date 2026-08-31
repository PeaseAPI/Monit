{{-- 缓存（cache）：只读运维面板——缓存驱动状态 + 清空缓存操作（原系统 cache 页） --}}
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">查看缓存状态并清空缓存。清空后设置、统计等业务缓存将自动重建。</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">缓存驱动（CACHE_STORE）</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">设置缓存（monit.settings）</dt>
            <dd class="text-sm font-medium {{ !empty($settings['settings_cached']) ? 'text-green-600' : 'text-zinc-500' }}">
                {{ !empty($settings['settings_cached']) ? '已缓存' : '未缓存（下次读取重建）' }}
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">设置缓存有效期</dt>
            <dd class="text-sm font-medium text-zinc-900">{{ $settings['settings_ttl_hours'] ?? 12 }} 小时（保存设置后自动失效）</dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.settings.clear_cache') }}">
        @csrf
        <button type="submit"
            class="rounded-xl bg-red-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-red-700"
            onclick="return confirm('确定清空全部缓存？')">
            清空缓存
        </button>
    </form>
</div>
