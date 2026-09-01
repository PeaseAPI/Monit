@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('seo.audits_title') }}</h1>
        <a href="{{ route('seo.audits.export') }}" class="rounded-lg border border-zinc-200 px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-50">CSV</a>
    </div>

                <form method="POST" action="{{ route('seo.audits.store') }}" class="mt-6 space-y-4 rounded-2xl border border-zinc-200 bg-white p-4" data-seo-form>
        @csrf

        {{-- 审计类型选择 --}}
        <div class="flex gap-2 text-sm">
            <label class="flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="type" value="single" checked class="accent-indigo-600"> {{ __('seo.type_single') }}
            </label>
            <label class="flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="type" value="sitemap" class="accent-indigo-600"> {{ __('seo.type_sitemap') }}
            </label>
            <label class="flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="type" value="bulk" class="accent-indigo-600"> {{ __('seo.type_bulk') }}
            </label>
            <label class="flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-2 cursor-pointer has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50">
                <input type="radio" name="type" value="html" class="accent-indigo-600"> {{ __('seo.type_html') }}
            </label>
        </div>

        {{-- Single / Sitemap：URL 输入 --}}
        <div id="field-url" class="flex gap-3">
            <input type="text" name="url" required placeholder="https://example.com/page" value="{{ old('url') }}"
                   class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm">
                {{-- Bulk：多 URL 输入 --}}
        <div id="field-bulk" class="hidden">
            <textarea name="urls" rows="6" placeholder="https://example.com/page1&#10;https://example.com/page2&#10;..." class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm">{{ old('urls') }}</textarea>
            <p class="mt-1 text-xs text-zinc-500">{{ __('seo.bulk_hint') }}</p>
        </div>

        {{-- HTML：源码输入 --}}
        <div id="field-html" class="hidden">
            <textarea name="html" rows="8" placeholder="{{ __('seo.html_placeholder') }}" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-mono">{{ old('html') }}</textarea>
        </div>

        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white">{{ __('seo.run_audit') }}</button>
    </form>

    <script>
    document.querySelectorAll('input[name="type"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            var type = this.value;
            document.getElementById('field-url').classList.toggle('hidden', type === 'bulk');
            document.getElementById('field-bulk').classList.toggle('hidden', type !== 'bulk');
            document.getElementById('field-html').classList.toggle('hidden', type !== 'html');
            document.querySelector('input[name="url"]').required = (type !== 'bulk');
        });
    });
    </script>
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
