@extends('layouts.app')
@section('title', __('seo.compare_title'))
@section('content')
<div class="max-w-7xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.compare_title') }}</h1>

    {{-- 选择表单 --}}
    <form method="GET" action="{{ route('seo.audits.compare') }}" class="mt-6 grid gap-4 rounded-2xl border border-zinc-200 bg-white p-6 sm:grid-cols-2">
        <label class="block">
            <span class="text-sm font-medium text-zinc-700">{{ __('seo.compare_audit_a') }}</span>
            <select name="audit_a" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="">{{ __('seo.compare_select_placeholder') }}</option>
                @foreach($availableAudits ?? [] as $a)
                    <option value="{{ $a->seo_audit_id }}" @selected(request('audit_a') == $a->seo_audit_id)>{{ $a->url }} ({{ $a->score }})</option>
                @endforeach
            </select>
        </label>
        <label class="block">
            <span class="text-sm font-medium text-zinc-700">{{ __('seo.compare_audit_b') }}</span>
            <select name="audit_b" class="mt-1 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                <option value="">{{ __('seo.compare_select_placeholder') }}</option>
                @foreach($availableAudits ?? [] as $b)
                    <option value="{{ $b->seo_audit_id }}" @selected(request('audit_b') == $b->seo_audit_id)>{{ $b->url }} ({{ $b->score }})</option>
                @endforeach
            </select>
        </label>
        <div class="sm:col-span-2">
            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.compare_submit') }}</button>
        </div>
    </form>

    {{-- 对比结果 --}}
    @if(isset($auditA) && isset($auditB))
    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        {{-- 审计 A --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-zinc-800">{{ $auditA->url }}</h2>
                <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $auditA->score > 79 ? 'bg-emerald-50 text-emerald-700' : ($auditA->score >= 50 ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700') }}">{{ $auditA->score }}/100</span>
            </div>
            <p class="mt-1 text-sm text-zinc-500">{{ $auditA->created_at?->format('Y-m-d H:i') }}</p>
            @foreach($auditA->resultsByCategory() as $category => $rows)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-zinc-700">{{ __("seo.category_{$category}") }}</h3>
                    <div class="mt-2 space-y-1">
                        @foreach($rows as $key => $row)
                            <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ ($row['passed'] ?? false) ? 'bg-emerald-50' : ($row['importance'] === 'major' ? 'bg-red-50' : 'bg-yellow-50') }}">
                                <span>{{ \Illuminate\Support\Str::headline($key) }}</span>
                                <span class="font-medium {{ ($row['passed'] ?? false) ? 'text-emerald-700' : 'text-red-700' }}">{{ ($row['passed'] ?? false) ? '✓' : (($row['value'] ?? '') ?: '✗') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 审计 B --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-zinc-800">{{ $auditB->url }}</h2>
                <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $auditB->score > 79 ? 'bg-emerald-50 text-emerald-700' : ($auditB->score >= 50 ? 'bg-yellow-50 text-yellow-700' : 'bg-red-50 text-red-700') }}">{{ $auditB->score }}/100</span>
            </div>
            <p class="mt-1 text-sm text-zinc-500">{{ $auditB->created_at?->format('Y-m-d H:i') }}</p>
            @foreach($auditB->resultsByCategory() as $category => $rows)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-zinc-700">{{ __("seo.category_{$category}") }}</h3>
                    <div class="mt-2 space-y-1">
                        @foreach($rows as $key => $row)
                            <div class="flex items-center justify-between rounded-lg px-3 py-2 text-sm {{ ($row['passed'] ?? false) ? 'bg-emerald-50' : ($row['importance'] === 'major' ? 'bg-red-50' : 'bg-yellow-50') }}">
                                <span>{{ \Illuminate\Support\Str::headline($key) }}</span>
                                <span class="font-medium {{ ($row['passed'] ?? false) ? 'text-emerald-700' : 'text-red-700' }}">{{ ($row['passed'] ?? false) ? '✓' : (($row['value'] ?? '') ?: '✗') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection