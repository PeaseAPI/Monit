{{-- Website form fields (shared by create/edit, fields match WebsiteController) --}}
<div>
    <label for="name" class="block text-sm font-medium text-zinc-700">{{ __('websites.name_label') }}</label>
    <input id="name" type="text" name="name" value="{{ old('name', $website->name ?? '') }}" required
           placeholder="{{ __('websites.name_placeholder') }}"
           class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('name') border-red-400 @enderror">
    @error('name') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    <p class="mt-1.5 text-xs text-zinc-400">{{ __('websites.name_hint') }}</p>
</div>

<div>
    <label for="url" class="block text-sm font-medium text-zinc-700">{{ __('websites.url_label') }}</label>
    <input id="url" type="url" name="url" value="{{ old('url', isset($website) ? $website->scheme.'://'.$website->host : '') }}" required
           placeholder="https://example.com"
           class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 @error('url') border-red-400 @enderror">
    @error('url') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    <p class="mt-1.5 text-xs text-zinc-400">{{ __('websites.url_hint') }}</p>
</div>

<div>
    <span class="block text-sm font-medium text-zinc-700">{{ __('admin.tracking_mode') }}</span>
    <div class="mt-2 grid gap-3 sm:grid-cols-2">
        @php $trackingType = old('tracking_type', $website->tracking_type ?? 'advanced'); @endphp
        <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition {{ $trackingType === 'advanced' ? 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' : 'border-zinc-200 bg-white' }}">
            <input type="radio" name="tracking_type" value="advanced" class="mt-1 text-brand-600 focus:ring-brand-500" {{ $trackingType === 'advanced' ? 'checked' : '' }}>
            <span>
                <span class="block text-sm font-semibold text-zinc-800">{{ __('websites.advanced_mode_label') }}</span>
                <span class="mt-0.5 block text-xs text-zinc-500">{{ __('websites.advanced_mode_desc') }}</span>
            </span>
        </label>
        <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition {{ $trackingType === 'lightweight' ? 'border-amber-400 bg-amber-50 ring-1 ring-amber-400' : 'border-zinc-200 bg-white' }}">
            <input type="radio" name="tracking_type" value="lightweight" class="mt-1 text-amber-600 focus:ring-amber-500" {{ $trackingType === 'lightweight' ? 'checked' : '' }}>
            <span>
                <span class="block text-sm font-semibold text-zinc-800">{{ __('websites.lightweight_mode_label') }}</span>
                <span class="mt-0.5 block text-xs text-zinc-500">{{ __('websites.lightweight_mode_desc') }}</span>
            </span>
        </label>
    </div>
    @error('tracking_type') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
</div>

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="timezone" class="block text-sm font-medium text-zinc-700">{{ __('websites.timezone_label') }}</label>
        <select id="timezone" name="timezone"
                class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
            @foreach (['Asia/Shanghai' => 'Asia/Shanghai', 'Asia/Hong_Kong' => 'Asia/Hong_Kong', 'Asia/Tokyo' => 'Asia/Tokyo', 'UTC' => 'UTC'] as $tz => $label)
                <option value="{{ $tz }}" {{ old('timezone', $website->timezone ?? 'Asia/Shanghai') === $tz ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('timezone') <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="excluded_ips" class="block text-sm font-medium text-zinc-700">{{ __('websites.excluded_ips_label') }}</label>
        <input id="excluded_ips" type="text" name="excluded_ips" value="{{ old('excluded_ips', $website->excluded_ips ?? '') }}"
               placeholder="1.2.3.4,10.0.0.0/24"
               class="mt-1.5 block w-full rounded-xl border-zinc-300 shadow-sm focus:border-brand-500 focus:ring-brand-500">
        <p class="mt-1.5 text-xs text-zinc-400">{{ __('websites.excluded_ips_hint') }}</p>
    </div>
</div>

<div class="space-y-3 rounded-xl border border-zinc-200 bg-zinc-50/60 p-4">
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="is_enabled" value="0">
        <input type="checkbox" name="is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('is_enabled', isset($website) ? (bool) $website->is_enabled : true) ? 'checked' : '' }}>
        {{ __('websites.enable_stats') }}
    </label>
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="bot_exclusion_is_enabled" value="0">
        <input type="checkbox" name="bot_exclusion_is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('bot_exclusion_is_enabled', isset($website) ? (bool) $website->bot_exclusion_is_enabled : true) ? 'checked' : '' }}>
        {{ __('websites.exclude_bots') }}
    </label>
    <label class="flex items-center gap-2 text-sm text-zinc-700">
        <input type="hidden" name="query_parameters_tracking_is_enabled" value="0">
        <input type="checkbox" name="query_parameters_tracking_is_enabled" value="1"
               class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
               {{ old('query_parameters_tracking_is_enabled', isset($website) ? (bool) $website->query_parameters_tracking_is_enabled : false) ? 'checked' : '' }}>
        {{ __('websites.track_query_params') }}
    </label>
</div>

