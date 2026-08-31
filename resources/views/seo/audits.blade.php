@extends('layouts.app')
@section('content')
<div class="p-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.audits_title') }}</h1>
        <a href="{{ route('seo.audits.export') }}" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">CSV</a>
    </div>

    <form method="POST" action="{{ route('seo.audits.store') }}" class="mt-6 flex gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
        @csrf
        <input type="url" name="url" required placeholder="https://example.com/page" value="{{ old('url') }}"
               class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.run_audit') }}</button>
    </form>
    @error('url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
            <tr>
                <th class="px-6 py-3 font-medium text-zinc-500">URL</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.score') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.issues') }}</th>
                <th class="px-6 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse($audits as $audit)
                <tr>
                    <td class="max-w-xs truncate px-6 py-3">{{ $audit->url }}</td>
                    <td class="px-6 py-3 font-semibold {{ ['poor' => 'text-red-600', 'decent' => 'text-yellow-600', 'good' => 'text-emerald-600'][$audit->band] }}">{{ $audit->score }}</td>
                    <td class="px-6 py-3">{{ $audit->status === 'completed' ? __('seo.completed') : __('seo.failed_status') }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $audit->major_issues }} / {{ $audit->moderate_issues }} / {{ $audit->minor_issues }}</td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="text-sm text-indigo-600 hover:underline">{{ __('seo.view_report') }}</a>
                        <form method="POST" action="{{ route('seo.audits.destroy', $audit->seo_audit_id) }}" class="inline" onsubmit="return confirm('{{ __('seo.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button class="ml-3 text-sm text-red-600 hover:underline">{{ __('common.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_audits') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $audits->links() }}
</div>
@endsection
