{{-- 健康检查（health）：只读运维面板——运行环境体检（原系统 health 页） --}}
@php
    $free = $settings['disk_free'] ?? null;
    $total = $settings['disk_total'] ?? null;
    $diskPercent = ($free && $total && $total > 0) ? round($free / $total * 100, 1) : null;
@endphp
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">系统运行环境体检，全部只读，无需保存。</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">PHP 版本</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['php'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">Laravel 版本</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['laravel'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">MySQL 版本（{{ $settings['database_driver'] ?? '-' }}）</dt>
            <dd class="font-mono text-sm font-medium {{ !empty($settings['mysql_version']) ? 'text-green-600' : 'text-red-600' }}">
                {{ $settings['mysql_version'] ?? '连接失败' }}
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">缓存驱动</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['cache_driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">队列驱动</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['queue_driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">磁盘剩余空间</dt>
            <dd class="text-sm font-medium {{ ($diskPercent !== null && $diskPercent < 10) ? 'text-red-600' : 'text-green-600' }}">
                @if($diskPercent !== null)
                    {{ number_format($free / 1073741824, 1) }} GB 可用 / {{ number_format($total / 1073741824, 1) }} GB（{{ $diskPercent }}%）
                @else
                    读取失败
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">时区</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['timezone'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">设置条目数</dt>
            <dd class="text-sm font-medium text-zinc-900">{{ number_format((float) ($settings['settings_count'] ?? 0)) }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">调度器（cron）</dt>
            <dd class="text-sm text-zinc-500">* * * * * php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</dd>
        </div>
    </dl>
</div>
