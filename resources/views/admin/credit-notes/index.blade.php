@extends('layouts.admin')
@section('title', __('admin.credit_notes'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.credit_notes') }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left"><tr>
                <th class="px-6 py-3 font-medium text-zinc-500">ID</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_user') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_amount') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_date') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_status') }}</th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($creditNotes ?? [] as $note)
                <tr>
                    <td class="px-6 py-3 font-mono text-xs text-zinc-500">{{ $note->credit_note_id ?? $note->id }}</td>
                    <td class="px-6 py-3 text-zinc-900">{{ $note->user->name ?? '-' }}</td>
                    <td class="px-6 py-3 text-zinc-700 font-medium">{{ $note->total_amount ?? $note->amount }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $note->datetime }}</td>
                    <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ ($note->status ?? 1) ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ ($note->status ?? 1) ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td>
                </tr>
                @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('common.no_data') }}</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection