{{-- 网站表单字段（create/edit 共用，字段与 WebsiteController 匹配） --}}
<div>
    <label for="name" class="block text-sm font-medium text-zinc-700">网站名称</label>
    <input id="name" type="text" name="name" value="{{ old('name', $website->name ?? '') }}" required
           placeholder="例如：我的博客"
           class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
    @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    <p class="mt-1.5 text-xs text-zinc-400">仅用于后台显示，方便你识别。</p>
</div>

<div>
    <label for="url" class="block text-sm font-medium text-zinc-700">网站地址</label>
    <input id="url" type="url" name="url" value="{{ old('url', isset($website) ? $website->scheme.'://'.$website->host : '') }}" required
           placeholder="https://example.com"
           class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('url') border-red-400 @enderror">
    @error('url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    <p class="mt-1.5 text-xs text-zinc-400">需以 http:// 或 https:// 开头；www. 前缀会自动去除。</p>
</div>

<div>
    <span class="block text-sm font-medium text-zinc-700">跟踪模式</span>
    <div class="mt-2 grid gap-3 sm:grid-cols-2">
        @php $trackingType = old('tracking_type', $website->tracking_type ?? 'advanced'); @endphp
        <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition {{ $trackingType === 'advanced' ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-zinc-200 bg-white' }}">
            <input type="radio" name="tracking_type" value="advanced" class="mt-1 text-brand-600 focus:ring-brand-500" {{ $trackingType === 'advanced' ? 'checked' : '' }}>
            <span>
                <span class="block text-sm font-semibold text-zinc-800">完整模式</span>
                <span class="mt-0.5 block text-xs text-zinc-500">记录完整事件流，支持事件分析与会话回放。</span>
            </span>
        </label>
        <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition {{ $trackingType === 'lightweight' ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-400' : 'border-zinc-200 bg-white' }}">
            <input type="radio" name="tracking_type" value="lightweight" class="mt-1 text-amber-600 focus:ring-amber-500" {{ $trackingType === 'lightweight' ? 'checked' : '' }}>
            <span>
                <span class="block text-sm font-semibold text-zinc-800">轻量模式</span>
                <span class="mt-0.5 block text-xs text-zinc-500">仅记录基础浏览数据，存储占用小，适合高流量。</span>
            </span>
        </label>
    </div>
    @error('tracking_type') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="timezone" class="block text-sm font-medium text-zinc-700">时区</label>
        <select id="timezone" name="timezone"
                class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach (['Asia/Shanghai' => 'Asia/Shanghai（北京时间）', 'Asia/Hong_Kong' => 'Asia/Hong_Kong', 'Asia/Tokyo' => 'Asia/Tokyo', 'UTC' => 'UTC'] as $tz => $label)
                <option value="{{ $tz }}" {{ old('timezone', $website->timezone ?? 'Asia/Shanghai') === $tz ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('timezone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="excluded_ips" class="block text-sm font-medium text-zinc-700">排除 IP（可选）</label>
        <input id="excluded_ips" type="text" name="excluded_ips" value="{{ old('excluded_ips', $website->excluded_ips ?? '') }}"
               placeholder="例如：1.2.3.4,10.0.0.0/24"
               class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        <p class="mt-1.5 text-xs text-zinc-400">多个用英文逗号分隔，支持 CIDR 网段。</p>
    </div>
</div>

<div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50/60 p-4">
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="is_enabled" value="0">
        <input type="checkbox" name="is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('is_enabled', isset($website) ? (bool) $website->is_enabled : true) ? 'checked' : '' }}>
        启用统计（取消勾选则暂停采集新数据）
    </label>
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="bot_exclusion_is_enabled" value="0">
        <input type="checkbox" name="bot_exclusion_is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('bot_exclusion_is_enabled', isset($website) ? (bool) $website->bot_exclusion_is_enabled : true) ? 'checked' : '' }}>
        排除爬虫 / 机器人流量
    </label>
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="query_parameters_tracking_is_enabled" value="0">
        <input type="checkbox" name="query_parameters_tracking_is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('query_parameters_tracking_is_enabled', isset($website) ? (bool) $website->query_parameters_tracking_is_enabled : false) ? 'checked' : '' }}>
        记录 URL 查询参数（区分 ?utm_source=... 等不同地址）
    </label>
</div>

