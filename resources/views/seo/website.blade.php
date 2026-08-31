@extends('layouts.app')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ $website->host }} · SEO</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('seo.avg_score') }}: {{ $website->seo_avg_score }} · {{ __('seo.total_audits') }}: {{ $website->seo_total_audits }}</p>

    <form method="POST" action="{{ route('websites.seo.update', $website->website_id) }}" class="mt-6 grid gap-4 rounded-2xl border border-zinc-200 bg-white p-6 md:grid-cols-2">
        @csrf @method('PUT')
        <label class="block"><span class="text-sm font-medium text-zinc-700">{{ __('seo.audit_interval') }}</span>
            <select name="seo_audit_check_interval" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                @foreach($intervals as $interval)<option value="{{ $interval }}" @selected($website->seo_audit_check_interval === $interval)>{{ __("seo.interval_{$interval}") }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-sm font-medium text-zinc-700">{{ __('seo.sitemap_interval') }}</span>
            <select name="seo_sitemap_check_interval" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                @foreach(['never', 'daily', 'weekly', 'monthly'] as $interval)<option value="{{ $interval }}" @selected($website->seo_sitemap_check_interval === $interval)>{{ __("seo.interval_{$interval}") }}</option>@endforeach
            </select></label>
        <label class="block md:col-span-2"><span class="text-sm font-medium text-zinc-700">{{ __('seo.sitemap_url') }}</span>
            <input type="url" name="seo_sitemap_url" value="{{ $website->seo_sitemap_url }}" placeholder="https://{{ $website->host }}/sitemap.xml" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm"></label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="seo_notifications_enabled" value="1" @checked($website->seo_notifications_enabled)> {{ __('seo.notifications_enabled') }}</label>
        <label class="block"><span class="text-sm font-medium text-zinc-700">{{ __('seo.notifications_mode') }}</span>
            <select name="seo_notifications_mode" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="always" @selected($website->seo_notifications_mode === 'always')>{{ __('seo.mode_always') }}</option>
                <option value="changes" @selected($website->seo_notifications_mode === 'changes')>{{ __('seo.mode_changes') }}</option>
            </select></label>
        <div class="md:col-span-2"><button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('common.save') }}</button></div>
    </form>

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left"><tr>
                <th class="px-6 py-3 font-medium text-zinc-500">URL</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.score') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.date') }}</th>
                <th class="px-6 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse($audits as $audit)
                <tr>
                    <td class="max-w-xs truncate px-6 py-3">{{ $audit->url }}</td>
                    <td class="px-6 py-3 font-semibold">{{ $audit->score }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $audit->created_at?->format('Y-m-d H:i') }}</td>
                    <td class="px-6 py-3 text-right"><a href="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="text-indigo-600 hover:underline">{{ __('seo.view_report') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_audits') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $audits->links() }}
</div>
@endsection
