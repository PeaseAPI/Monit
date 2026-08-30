@extends('layouts.admin')
@section('title', 'Image Optimizer')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.plugins.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; 返回插件列表</a>
    <h1 class="mt-2 text-2xl font-bold text-zinc-900">📸 Image Optimizer - 压缩统计</h1>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">累计处理体积</p>
        <p class="mt-1 text-3xl font-bold text-zinc-900">{{ number_format($totalOriginal / 1024, 1) }} KB</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">优化后体积</p>
        <p class="mt-1 text-3xl font-bold text-zinc-900">{{ number_format($totalOptimized / 1024, 1) }} KB</p>
    </div>
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <p class="text-sm text-zinc-500">节省空间</p>
        <p class="mt-1 text-3xl font-bold text-green-600">{{ number_format($saved / 1024, 1) }} KB（{{ $savedPercent }}%）</p>
    </div>
</div>

<div class="mt-6 flex items-center justify-between">
    <h2 class="text-lg font-semibold text-zinc-900">最近压缩记录</h2>
    <form method="POST" action="{{ route('admin.plugins.image-optimizer.batch') }}"
          onsubmit="return confirm('批量优化 uploads/ 目录下的图片（每批 50 张）？')">@csrf
        <button class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">批量优化 uploads/</button>
    </form>
</div>

<div class="mt-4 overflow-x-auto rounded-2xl border border-zinc-200 bg-white p-6">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-zinc-200 text-left text-zinc-500">
            <th class="py-2 pr-4">时间</th><th class="py-2 pr-4">类型</th><th class="py-2 pr-4">原始</th><th class="py-2 pr-4">优化后</th><th class="py-2">节省</th>
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
            <tr><td colspan="5" class="py-4 text-center text-zinc-400">暂无记录</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
