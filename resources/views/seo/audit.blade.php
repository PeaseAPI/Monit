@extends('layouts.public')
@section('title', $audit->url)
@section('content')
<div class="space-y-8">
    {{-- ===== Hero: Score Gauge + Meta ===== --}}
    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
        <div class="flex flex-col gap-6 p-6 md:flex-row md:items-center md:gap-8 md:p-8">
            {{-- Ring Gauge --}}
            <div class="relative flex shrink-0 items-center justify-center">
                @php
                    $score = (int) $audit->score;
                    $circumference = 2 * pi() * 54;
                    $offset = $circumference - ($score / 100) * $circumference;
                    $scoreColor = $score > 79 ? 'text-emerald-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-600');
                    $ringColor  = $score > 79 ? 'stroke-emerald-500' : ($score >= 50 ? 'stroke-amber-500' : 'stroke-red-500');
                @endphp
                <svg width="140" height="140" viewBox="0 0 120 120" class="-rotate-90">
                    <circle cx="60" cy="60" r="54" stroke-width="10" fill="none" class="stroke-zinc-100"/>
                    <circle cx="60" cy="60" r="54" stroke-width="10" fill="none"
                            class="{{ $ringColor }} transition-all duration-700"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $offset }}"/>
                </svg>
                <div class="absolute flex flex-col items-center">
                    <span class="text-4xl font-bold {{ $scoreColor }}">{{ $score }}</span>
                    <span class="text-xs text-zinc-400">/100</span>
                </div>
            </div>

            {{-- Meta --}}
            <div class="min-w-0 flex-1">
                <h1 class="truncate text-xl font-bold text-zinc-900 md:text-2xl">{{ $audit->url }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        {{ $audit->response_time_ms }} ms
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                        {{ number_format($audit->page_size_bytes / 1024, 1) }} KB
                    </span>
                    <span class="flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                        {{ $audit->created_at?->format('Y-m-d H:i') }}
                    </span>
                </div>
                {{-- Action Buttons --}}
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ ['poor' => 'bg-red-50 text-red-700', 'decent' => 'bg-amber-50 text-amber-700', 'good' => 'bg-emerald-50 text-emerald-700'][$audit->band] }}">
                        @if($audit->band === 'good')✅@elseif($audit->band === 'decent')⚠️@else❌@endif
                        {{ $audit->passed_tests }}/{{ $audit->total_tests }} {{ __('seo.passed') }}
                    </span>
                    <a href="{{ route('seo.audits.pdf', $audit->seo_audit_id) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm text-zinc-700 transition hover:bg-zinc-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        {{ __('seo.download_pdf') }}
                    </a>
                    @auth
                        <form method="POST" action="{{ route('seo.audits.refresh', $audit->seo_audit_id) }}" class="inline">
                            @csrf
                            <button class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm text-zinc-700 transition hover:bg-zinc-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                                {{ __('seo.re_audit') }}
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
        </div>

            {{-- Issues Overview Bar --}}
        @php
            $total = max(1, $audit->total_tests);
            $majorPctStyle   = $audit->major_issues   ? 'style="width:'.round($audit->major_issues  /$total*100,1).'%"' : '';
            $moderatePctStyle = $audit->moderate_issues ? 'style="width:'.round($audit->moderate_issues/$total*100,1).'%"' : '';
            $minorPctStyle   = $audit->minor_issues   ? 'style="width:'.round($audit->minor_issues  /$total*100,1).'%"' : '';
            $passedPctStyle  = $audit->passed_tests    ? 'style="width:'.round($audit->passed_tests  /$total*100,1).'%"' : '';
        @endphp
        <div class="border-t border-zinc-100 px-6 py-4 md:px-8">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700">{{ __('seo.issues_overview') }}</span>
            </div>
            <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-zinc-100">
                @if($audit->major_issues)<div class="bg-red-500 transition-all" {!! $majorPctStyle !!}></div>@endif
                @if($audit->moderate_issues)<div class="bg-amber-400 transition-all" {!! $moderatePctStyle !!}></div>@endif
                @if($audit->minor_issues)<div class="bg-zinc-400 transition-all" {!! $minorPctStyle !!}></div>@endif
                @if($audit->passed_tests)<div class="bg-emerald-500 transition-all" {!! $passedPctStyle !!}></div>@endif
            </div>
            <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-zinc-500">
                <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-red-500"></span>{{ __('seo.major_issue') }}: {{ $audit->major_issues }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-amber-400"></span>{{ __('seo.moderate_issue') }}: {{ $audit->moderate_issues }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-zinc-400"></span>{{ __('seo.minor_issue') }}: {{ $audit->minor_issues }}</span>
                <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('seo.passed') }}: {{ $audit->passed_tests }}</span>
            </div>
        </div>
    </div>

        {{-- ===== Category Score Cards ===== --}}
    @if($audit->category_scores)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($audit->category_scores as $category => $catScore)
                @php
                    $catBand = $catScore > 79 ? 'good' : ($catScore >= 50 ? 'decent' : 'poor');
                    $catColor = $catBand === 'good' ? 'emerald' : ($catBand === 'decent' ? 'amber' : 'red');
                    $catPassed = collect($audit->results)->filter(fn($r) => ($r['category'] ?? '') === $category && ($r['passed'] ?? false))->count();
                    $catTotal = collect($audit->results)->filter(fn($r) => ($r['category'] ?? '') === $category)->count();
                    $catBarStyle = $catScore !== null ? 'style="width:'.$catScore.'%"' : '';
                @endphp
                <div class="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-medium text-zinc-700">{{ __("seo.category_{$category}") }}</h3>
                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ $catBand === 'good' ? 'bg-emerald-50 text-emerald-700' : ($catBand === 'decent' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $catScore }}</span>
                    </div>
                    <div class="mt-3 h-1.5 w-full rounded-full bg-zinc-100">
                        <div class="h-1.5 rounded-full transition-all {{ $catBand === 'good' ? 'bg-emerald-500' : ($catBand === 'decent' ? 'bg-amber-400' : 'bg-red-500') }}" {!! $catBarStyle !!}></div>
                    </div>
                    <p class="mt-2 text-xs text-zinc-400">{{ $catPassed }}/{{ $catTotal }} {{ __('seo.passed') }}</p>
                </div>
            @endforeach
        </div>
    @endif

        {{-- ===== Share / Privacy Settings ===== --}}
    @if(auth()->check() && (int)$audit->user_id === (int)auth()->user()->user_id)
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <h3 class="text-sm font-medium text-zinc-700">{{ __('seo.share') }}</h3>
            <form method="POST" action="{{ route('seo.audits.share', $audit->seo_audit_id) }}" class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                @csrf
                <select name="privacy" class="rounded-lg border border-zinc-200 px-3 py-1.5 text-sm">
                    @foreach(['public' => __('seo.public'), 'private' => __('seo.private'), 'password' => __('seo.password')] as $value => $label)
                        <option value="{{ $value }}" @selected($audit->privacy === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="password" placeholder="{{ __('seo.share_password_hint') }}" class="w-40 rounded-lg border border-zinc-200 px-3 py-1.5 text-sm">
                <label class="flex items-center gap-2 text-zinc-600"><input type="checkbox" name="is_public_directory" value="1" @checked($audit->is_public_directory)> {{ __('seo.list_in_directory') }}</label>
                <button class="rounded-lg bg-zinc-900 px-4 py-1.5 text-sm text-white transition hover:bg-zinc-800">{{ __('common.save') }}</button>
            </form>
        </div>
    @endif

    {{-- ===== AI Summary ===== --}}
    @if(auth()->check() && (int)$audit->user_id === (int)auth()->user()->user_id && \App\Services\Ai\AiService::isEnabled() && !$audit->ai_summary)
        <form method="POST" action="{{ route('seo.audits.ai', $audit->seo_audit_id) }}">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z"/></svg>
                {{ __('seo.generate_ai_summary') }}
            </button>
        </form>
    @endif

    @if($audit->ai_summary)
        <div class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-violet-50 p-5">
            <div class="mb-2 flex items-center gap-2 text-sm font-medium text-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z"/></svg>
                AI {{ __('seo.issues_overview') }}
            </div>
            <p class="text-sm leading-7 text-indigo-900">{{ $audit->ai_summary }}</p>
        </div>
    @endif

        {{-- ===== Share Link + QR ===== --}}
    @if($audit->privacy === 'public' || $audit->privacy === 'password')
        <div class="rounded-2xl border border-zinc-200 bg-white p-5">
            <h3 class="text-sm font-medium text-zinc-700">{{ __('seo.share_link') }}</h3>
            <div class="mt-3 flex items-center gap-3">
                <input type="text" readonly value="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="flex-1 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm" onclick="this.select()">
                <button data-copy="{{ route('seo.audits.show', $audit->seo_audit_id) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-zinc-900 px-4 py-2 text-sm text-white transition hover:bg-zinc-800">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                    {{ __('seo.copy_link') }}
                </button>
            </div>
            @php
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode(route('seo.audits.show', $audit->seo_audit_id));
            @endphp
            <div class="mt-3"><img src="{{ $qrUrl }}" alt="QR" class="h-24 w-24 rounded-lg border border-zinc-200"></div>
        </div>
    @endif

        {{-- ===== Test Results by Category ===== --}}
    @foreach($grouped as $category => $rows)
        @php
            $catScore = $audit->category_scores[$category] ?? null;
            $catBand = $catScore > 79 ? 'good' : ($catScore >= 50 ? 'decent' : 'poor');
            $failCount = collect($rows)->filter(fn($r) => !($r['passed'] ?? false))->count();
        @endphp
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-3">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold {{ $catBand === 'good' ? 'bg-emerald-50 text-emerald-700' : ($catBand === 'decent' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">{{ $catScore ?? '-' }}</span>
                    <h2 class="font-semibold text-zinc-800">{{ __("seo.category_{$category}") }}</h2>
                </div>
                <div class="flex items-center gap-3 text-sm text-zinc-500">
                    @if($failCount > 0)
                        <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">{{ $failCount }} {{ __('seo.fail') }}</span>
                    @endif
                    <span>{{ $catScore ?? '-' }}/100</span>
                </div>
            </div>
            <div class="divide-y divide-zinc-100">
                @foreach($rows as $key => $row)
                    <div id="{{ $key }}" class="flex items-start gap-4 px-6 py-3.5 transition hover:bg-zinc-50/50">
                        <div class="pt-0.5">
                            @if(($row['passed'] ?? false))
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                </span>
                            @else
                                <span class="flex h-5 w-5 items-center justify-center rounded-full {{ ($row['importance'] ?? 'minor') === 'major' ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600' }}">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-zinc-800">{{ \Illuminate\Support\Str::headline($key) }}</div>
                            @if(!empty($row['sub']))
                                <div class="mt-0.5 flex flex-wrap gap-x-3 text-xs text-zinc-400">
                                    @foreach($row['sub'] as $subKey)
                                        <span>{{ __("seo.sub.{$subKey}") }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if(!empty($row['detail']))
                                <div class="mt-0.5 text-xs text-zinc-500">{{ $row['detail'] }}</div>
                            @endif
                        </div>
                        <div class="shrink-0 text-sm text-zinc-500">{{ $row['value'] ?? '-' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

<script>
document.querySelectorAll('[data-copy]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        navigator.clipboard.writeText(this.dataset.copy);
        var btnEl = this;
        btnEl.classList.add('bg-emerald-600');
        btnEl.innerHTML = '{{ __("seo.copy_link") }} ✓';
        setTimeout(function() {
            btnEl.classList.remove('bg-emerald-600');
            btnEl.innerHTML = '<svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>{{ __("seo.copy_link") }}';
        }, 2000);
    });
});
</script>
@endsection
