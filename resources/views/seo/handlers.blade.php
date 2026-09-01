@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.handlers_title') }}</h1>

    <form method="POST" action="{{ route('seo.handlers.store') }}" class="mt-6 grid gap-3 rounded-2xl border border-zinc-200 bg-white p-6 md:grid-cols-2">
        @csrf
        <label class="block"><span class="text-sm font-medium text-zinc-700">{{ __('seo.name') }}</span>
            <input name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm"></label>
        <label class="block"><span class="text-sm font-medium text-zinc-700">{{ __('seo.channel') }}</span>
            <select name="type" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                @foreach($types as $type)<option value="{{ $type }}" @selected(old('type') === $type)>{{ __("seo.channel_{$type}") }}</option>@endforeach
            </select></label>
        <label class="block md:col-span-2"><span class="text-sm font-medium text-zinc-700">{{ __('seo.webhook_url') }} / Token</span>
            <input name="settings[webhook_url]" value="{{ old('settings.webhook_url') }}" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm"></label>
        <div class="md:col-span-2">
            <span class="text-sm font-medium text-zinc-700">{{ __('seo.events') }}</span>
            <div class="mt-2 flex flex-wrap gap-4 text-sm">
                @foreach(['audit_refreshed', 'audit_failed', 'sitemap_changed', 'domain_expiring'] as $event)
                    <label class="flex items-center gap-2"><input type="checkbox" name="events[]" value="{{ $event }}" @checked(in_array($event, old('events', ['audit_refreshed'])))> {{ __("seo.event_{$event}") }}</label>
                @endforeach
            </div>
        </div>
        @error('name')<p class="md:col-span-2 text-sm text-red-600">{{ $message }}</p>@enderror
        <div class="md:col-span-2"><button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.add_handler') }}</button></div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left"><tr>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.name') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.channel') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.events') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th>
                <th class="px-6 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse($handlers as $handler)
                <tr>
                    <td class="px-6 py-3">{{ $handler->name }}</td>
                    <td class="px-6 py-3">{{ __("seo.channel_{$handler->type}") }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ collect($handler->settings['events'] ?? [])->map(fn ($e) => __("seo.event_{$e}"))->implode('、') }}</td>
                    <td class="px-6 py-3">{{ $handler->is_enabled ? __('common.enabled') : __('common.disabled') }}</td>
                    <td class="px-6 py-3 text-right">
                        <form method="POST" action="{{ route('seo.handlers.destroy', $handler->notification_handler_id) }}" class="inline" onsubmit="return confirm('{{ __('seo.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-600 hover:underline">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_data') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
