@extends('layouts.guest')
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="max-w-2xl truncate text-2xl font-bold text-zinc-900">{{ $audit->url }}</h1>
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ ['poor' => 'bg-red-50 text-red-700', 'decent' => 'bg-yellow-50 text-yellow-700', 'good' => 'bg-emerald-50 text-emerald-700'][$audit->band] }}">{{ $audit->score }}/100 · {{ $audit->passed_tests }}/{{ $audit->total_tests }}</span>
    </div>
    <p class="mt-1 text-sm text-zinc-500">{{ __('seo.response_time') }}: {{ $audit->response_time_ms }} ms · {{ __('seo.page_size') }}: {{ number_format($audit->page_size_bytes / 1024, 1) }} KB · {{ $audit->created_at?->format('Y-m-d H:i') }}</p>

        @if($audit->category_scores)
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($audit->category_scores as $category => $catScore)
                <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-zinc-700">{{ __("seo.category_{$category}") }}</h3>
                    <div class="mt-2 flex items-end gap-2">
                        <span class="text-2xl font-bold {{ $catScore > 79 ? 'text-emerald-600' : ($catScore > 49 ? 'text-yellow-600' : 'text-red-600') }}">{{ $catScore }}</span>
                        <span class="text-sm text-zinc-400">/100</span>
                    </div>
                    <div class="mt-2 h-2 w-full rounded-full bg-zinc-100">
                        <div class="h-2 rounded-full {{ $catScore > 79 ? 'bg-emerald-500' : ($catScore > 49 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $catScore }}%"></div>
                    </div>
                    @php
                        $catResults = collect($audit->results)->filter(fn($r) => ($r['category'] ?? '') === $category);
                        $catPassed = $catResults->filter(fn($r) => $r['passed'] ?? false)->count();
                        $catTotal = $catResults->count();
                    @endphp
                    <p class="mt-1 text-xs text-zinc-500">{{ $catPassed }}/{{ $catTotal }} {{ __('seo.passed') }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if(auth()->check() && (int)$audit->user_id === (int)auth()->user()->user_id)
        <form method="POST" action="{{ route('seo.audits.share', $audit->seo_audit_id) }}" class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4 text-sm">
            @csrf
            <span class="font-medium text-zinc-700">{{ __('seo.share') }}:</span>
            <select name="privacy" class="rounded-lg border border-zinc-200 px-2 py-1">
                @foreach(['public' => __('seo.public'), 'private' => __('seo.private'), 'password' => __('seo.password')] as $value => $label)
                    <option value="{{ $value }}" @selected($audit->privacy === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="password" placeholder="{{ __('seo.share_password_hint') }}" class="w-40 rounded-lg border border-zinc-200 px-2 py-1">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_public_directory" value="1" @checked($audit->is_public_directory)> {{ __('seo.list_in_directory') }}</label>
            <button class="rounded-lg bg-zinc-900 px-3 py-1.5 text-white">{{ __('common.save') }}</button>
        </form>
    @endif

            @if(auth()->check() && (int)$audit->user_id === (int)auth()->user()->user_id && \App\Services\Ai\AiService::isEnabled() && !$audit->ai_summary)
            <form method="POST" action="{{ route('seo.audits.ai', $audit->seo_audit_id) }}" class="mt-6">
                @csrf
                <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm text-indigo-700 hover:bg-indigo-100">{{ __('seo.generate_ai_summary') }}</button>
            </form>
        @endif
        @if($audit->ai_summary)
        <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm leading-6 text-indigo-900">{{ $audit->ai_summary }}</div>
        @endif

    {{-- 分享链接 + QR 码 --}}
    @if($audit->privacy === 'public' || $audit->privacy === 'password')
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-4">
            <h3 class="text-sm font-medium text-zinc-700">{{ __('seo.share_link') }}</h3>
            <div class="mt-2 flex items-center gap-3">
                <input type="text" readonly value="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm" onclick="this.select()">
                <button onclick="navigator.clipboard.writeText('{{ route('seo.audits.show', $audit->seo_audit_id) }}')" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm text-white">{{ __('seo.copy_link') }}</button>
            </div>
            @php
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode(route('seo.audits.show', $audit->seo_audit_id));
            @endphp
            <div class="mt-3"><img src="{{ $qrUrl }}" alt="QR" class="h-28 w-28 rounded-lg border border-zinc-200"></div>
        </div>
    @endif

    @foreach($grouped as $category => $rows)
        <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="flex items-center justify-between bg-zinc-50 px-6 py-3">
                <h2 class="font-semibold text-zinc-800">{{ __("seo.category_{$category}") }}</h2>
                <span class="text-sm text-zinc-500">{{ $audit->category_scores[$category] ?? '-' }}/100</span>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-zinc-100">
                @foreach($rows as $key => $row)
                    <tr>
                        <td class="px-6 py-3 w-24">
                            <span class="rounded-full px-2 py-1 text-xs {{ ($row['passed'] ?? false) ? 'bg-emerald-50 text-emerald-700' : ($row['importance'] === 'major' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700') }}">{{ ($row['passed'] ?? false) ? __('seo.pass') : __('seo.fail') }}</span>
                        </td>
                        <td class="px-2 py-3 text-zinc-700">{{ \Illuminate\Support\Str::headline($key) }}</td>
                        <td class="px-6 py-3 text-right text-zinc-500">{{ $row['value'] ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
