@extends('layouts.admin')
@section('title', 'Image Optimizer')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.plugins.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('plugins.back_to_list') }}</a>
    <h1 class="mt-2 text-2xl font-bold text-zinc-900">📸 Image Optimizer - {{ __('plugins.imgopt.compression_stats') }}</h1>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">{{ __('plugins.imgopt.total_processed') }}</p>
        <p class="mt-1 text-3xl font-bold text-zinc-900">{{ number_format($totalOriginal / 1024, 1) }} KB</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">{{ __('plugins.imgopt.optimized_size') }}</p>
        <p class="mt-1 text-3xl font-bold text-zinc-900">{{ number_format($totalOptimized / 1024, 1) }} KB</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">{{ __('plugins.imgopt.space_saved') }}</p>
        <p class="mt-1 text-3xl font-bold text-green-600">{{ number_format($saved / 1024, 1) }} KB（{{ $savedPercent }}%）</p>
    </div>
</div>

<div class="mt-6 flex items-center justify-between">
    <h2 class="text-lg font-semibold text-zinc-900">{{ __('plugins.imgopt.recent_records') }}</h2>
        <form method="POST" action="{{ route('admin.plugins.image-optimizer.batch') }}"
          onsubmit="return confirm(@json(__('plugins.imgopt.batch_confirm')))">@csrf
        <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ __('plugins.imgopt.batch_optimize') }}</button>
    </form>
</div>

<div class="mt-4 overflow-x-auto rounded-2xl border border-zinc-200 bg-white p-6">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-zinc-200 text-left text-zinc-500">
            <th class="py-2 pr-4">{{ __('plugins.imgopt.col_time') }}</th><th class="py-2 pr-4">{{ __('plugins.imgopt.col_type') }}</th><th class="py-2 pr-4">{{ __('plugins.imgopt.col_original') }}</th><th class="py-2 pr-4">{{ __('plugins.imgopt.col_optimized') }}</th><th class="py-2">{{ __('plugins.imgopt.col_saved') }}</th>
        </tr></thead>
        <tbody>
        @forelse($recent as $stat)
            <tr class="border-b border-zinc-100">
                <td class="py-2 pr-4 text-zinc-600">{{ optional($stat->datetime)->format('Y-m-d H:i') }}</td>
                <td class="py-2 pr-4 uppercase text-zinc-600">{{ $stat->file_type }}</td>
                <td class="py-2 pr-4 text-zinc-600">{{ number_format($stat->original_size / 1024, 1) }} KB</td>
                <td class="py-2 pr-4 text-zinc-600">{{ number_format($stat->optimized_size / 1024, 1) }} KB</td>
                <td class="py-2 text-green-600">{{ $stat->original_size > 0 ? round(($stat->original_size - $stat->optimized_size) / $stat->original_size * 100, 1) : 0 }}%</td>
            </tr>
        @empty
            <tr><td colspan="5" class="py-4 text-center text-zinc-400">{{ __('plugins.imgopt.no_records') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
