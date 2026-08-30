@extends('layouts.admin')
@section('title', __('admin.statistics_database'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_database') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_database_desc') }}</p>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white">
    <div class="border-b border-zinc-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.table_row_counts') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.table_name') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.row_count') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach($stats as $table => $count)
                <tr>
                    <td class="px-6 py-3 font-mono text-sm">{{ $table }}</td>
                    <td class="px-6 py-3">
                        @if($count >= 0)
                            {{ number_format($count) }}
                        @else
                            <span class="text-red-500">{{ __('admin.table_unavailable') }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
