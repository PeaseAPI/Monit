{{-- 健康检查（health）：只读运维面板——运行环境体检（原系统 health 页） --}}
@php
    $free = $settings['disk_free'] ?? null;
    $total = $settings['disk_total'] ?? null;
    $diskPercent = ($free && $total && $total > 0) ? round($free / $total * 100, 1) : null;
@endphp
<div class="max-w-3xl space-y-6">
    <p class="text-sm text-zinc-500">{{ __('settings.health.desc') }}</p>

    <dl class="divide-y divide-zinc-200 rounded-xl border border-zinc-200">
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.php_version') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['php'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.laravel_version') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['laravel'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
                        <dt class="text-sm text-zinc-500">{{ __('settings.health.mysql_version') }}（{{ $settings['database_driver'] ?? '-' }}）</dt>
            <dd class="font-mono text-sm font-medium {{ !empty($settings['mysql_version']) ? 'text-green-600' : 'text-red-600' }}">
                {{ $settings['mysql_version'] ?? __('settings.health.connection_failed') }}
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.cache_driver') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['cache_driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.queue_driver') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['queue_driver'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.disk_free') }}</dt>
            <dd class="text-sm font-medium {{ ($diskPercent !== null && $diskPercent < 10) ? 'text-red-600' : 'text-green-600' }}">
                @if($diskPercent !== null)
                                        {{ number_format($free / 1073741824, 1) }} GB {{ __('settings.health.available') }} / {{ number_format($total / 1073741824, 1) }} GB（{{ $diskPercent }}%）
                @else
                                        {{ __('settings.health.read_failed') }}
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.timezone') }}</dt>
            <dd class="font-mono text-sm font-medium text-zinc-900">{{ $settings['timezone'] ?? '-' }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.geoip') }}</dt>
            <dd class="text-right text-sm font-medium {{ !empty($settings['geoip_available']) ? 'text-green-600' : 'text-amber-600' }}">
                @if(!empty($settings['geoip_available']))
                    {{ __('settings.health.geoip_ok') }}
                @else
                    {{ __('settings.health.geoip_missing') }}<br>
                    <code class="text-xs text-zinc-500">php artisan geoip:update</code>
                @endif
            </dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.settings_count') }}</dt>
            <dd class="text-sm font-medium text-zinc-900">{{ number_format((float) ($settings['settings_count'] ?? 0)) }}</dd>
        </div>
        <div class="flex items-center justify-between px-4 py-3">
            <dt class="text-sm text-zinc-500">{{ __('settings.health.scheduler') }}</dt>
            <dd class="text-sm text-zinc-500">* * * * * php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</dd>
        </div>
    </dl>
</div>
