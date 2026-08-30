@extends('layouts.admin')
@section('title', __('admin.statistics_local_files'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.statistics_local_files') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.statistics_local_files_desc') }}</p>
</div>
<div class="grid gap-6 md:grid-cols-2">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.total_files') }}</p>
        <p class="mt-2 text-3xl font-bold text-zinc-900">{{ number_format($fileStats['total_files'] ?? 0) }}</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm font-medium text-zinc-500">{{ __('admin.total_disk_usage') }}</p>
                <p class="mt-2 text-3xl font-bold text-zinc-900">{{ number_format(($fileStats['total_size'] ?? 0) / 1048576, 2) }} MB</p>
    </div>
</div>
<div class="mt-6 rounded-2xl border border-zinc-200 bg-white">
    <div class="border-b border-zinc-200 px-6 py-4">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('admin.directories') }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left">
                <tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.directory') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.size') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($fileStats['directories'] ?? [] as $dir => $size)
                <tr>
                    <td class="px-6 py-3 font-mono text-sm">{{ $dir }}</td>
                    <td class="px-6 py-3">{{ $size }}</td>
                </tr>
                @empty
                <tr><td class="px-6 py-8 text-center text-zinc-500" colspan="2">{{ __('admin.no_directories') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
