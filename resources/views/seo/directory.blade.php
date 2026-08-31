@extends('layouts.guest')
@section('content')
<div class="p-8">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.directory_title') }}</h1>

    <form method="POST" action="{{ route('seo.analyze') }}" class="mt-6 flex max-w-xl gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
        @csrf
        <input type="url" name="url" required placeholder="https://example.com" value="{{ old('url') }}"
               class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.free_analyze') }}</button>
    </form>
    @error('url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

    <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left"><tr>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.host') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.score') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('seo.date') }}</th>
                <th class="px-6 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
            @forelse($audits as $audit)
                <tr>
                    <td class="px-6 py-3">{{ $audit->host }}</td>
                    <td class="px-6 py-3 font-semibold {{ ['poor' => 'text-red-600', 'decent' => 'text-yellow-600', 'good' => 'text-emerald-600'][$audit->band] }}">{{ $audit->score }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $audit->created_at?->format('Y-m-d') }}</td>
                    <td class="px-6 py-3 text-right"><a href="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="text-indigo-600 hover:underline">{{ __('seo.view_report') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-6 py-8 text-center text-zinc-500">{{ __('seo.no_data') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $audits->links() }}
</div>
@endsection
