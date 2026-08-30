<div class="space-y-6">
    <p class="text-sm text-zinc-500">配置定时任务（Cron）设置（规格书 §13）</p>

    <div class="space-y-4">
        <div>
            <label class="form-label">Cron 密钥</label>
            <div class="flex gap-2">
                <input type="text" name="cron_key" class="form-input flex-1" value="{{ old('cron_key', $settings['cron.cron_key'] ?? \Illuminate\Support\Str::random(32)) }}">
                <button type="button" class="btn btn-secondary" onclick="this.previousElementSibling.value=Math.random().toString(36).substr(2,32)">重新生成</button>
            </div>
            <p class="text-xs text-zinc-500 mt-1">Cron URL: {{ url('/cron') }}?key={{ $settings['cron.cron_key'] ?? '' }}</p>
        </div>
        <div>
            <label class="form-label">最后执行时间</label>
            <input type="text" class="form-input" value="{{ $settings['cron.cron_last_datetime'] ?? '从未执行' }}" disabled>
        </div>
    </div>
</div>

