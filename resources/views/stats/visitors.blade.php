@extends('layouts.app')
@section('content')
<div class="p-8">
    <x-stats-header :website="$website" :title="__('stats.visitors_title')" />
    {{-- M22 导出入口（原版 Visitors.php export，规格书 §6.2.2） --}}
    <div class="mb-4 flex items-center gap-2">
        <a href="{{ route('stats.visitors', array_filter(['website' => $website->website_id, 'export' => 'json'])) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.export_json') }}</a>
        <a href="{{ route('stats.visitors', array_filter(['website' => $website->website_id, 'export' => 'csv'])) }}" class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-600 shadow-sm hover:bg-zinc-50">{{ __('stats.export_csv') }}</a>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.visitors_id') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.visitor_sessions') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.visitor_pageviews') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.visitor_source') }}</th></tr></thead>
        <tbody class="divide-y divide-zinc-100">
            @forelse($visitors ?? [] as $v)<tr><td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $v->visitor_id }}</td><td class="px-6 py-3">{{ $v->sessions }}</td><td class="px-6 py-3">{{ $v->pageviews }}</td><td class="px-6 py-3 text-zinc-500">{{ $v->referrer ?? __('stats.direct_visit') }}</td></tr>
            @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="4">{{ __('stats.no_data') }}</td></tr>@endforelse
        </tbody></table>
    </div>
</div>
@endsection