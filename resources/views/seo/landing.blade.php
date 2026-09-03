@extends('layouts.public')
@section('title', __('seo.landing_title'))
@section('meta_description', __('seo.landing_description'))
@section('canonical', route('seo.landing'))
@section('content')
<div>
    {{-- Hero --}}
    <div class="text-center py-16">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            {{ $testCount }} {{ __('seo.landing_tests_available') }}
        </span>
        <h1 class="mt-6 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">{{ __('seo.landing_header') }}</h1>
        <p class="mx-auto mt-4 max-w-2xl text-lg text-zinc-500">{{ __('seo.landing_subheader') }}</p>

        <form method="POST" action="{{ route('seo.analyze') }}" class="mx-auto mt-8 flex max-w-xl gap-3 rounded-2xl border border-zinc-200 bg-white p-4">
            @csrf
            <input type="text" name="url" required placeholder="example.com" value="{{ old('url') }}"
                   class="flex-1 rounded-lg border border-zinc-200 px-3 py-2 text-sm" autofocus>
            <button type="submit" class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('seo.free_analyze') }}</button>
        </form>
        @error('url')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    {{-- Features --}}
    <div class="mx-auto max-w-5xl pb-16">
        <h2 class="text-center text-2xl font-bold text-zinc-900">{{ __('seo.landing_features') }}</h2>
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach([
                ['icon' => '🔍', 'title' => __('seo.landing_feature_audit_title'), 'desc' => __('seo.landing_feature_audit_desc')],
                ['icon' => '📊', 'title' => __('seo.landing_feature_score_title'), 'desc' => __('seo.landing_feature_score_desc')],
                ['icon' => '🤖', 'title' => __('seo.landing_feature_ai_title'), 'desc' => __('seo.landing_feature_ai_desc')],
                ['icon' => '🔗', 'title' => __('seo.landing_feature_backlinks_title'), 'desc' => __('seo.landing_feature_backlinks_desc')],
                ['icon' => '📈', 'title' => __('seo.landing_feature_keywords_title'), 'desc' => __('seo.landing_feature_keywords_desc')],
                ['icon' => '🛠️', 'title' => __('seo.landing_feature_tools_title'), 'desc' => __('seo.landing_feature_tools_desc')],
            ] as $feature)
                <div class="rounded-2xl border border-zinc-200 bg-white p-6">
                    <div class="text-2xl">{{ $feature['icon'] }}</div>
                    <h3 class="mt-3 font-semibold text-zinc-900">{{ $feature['title'] }}</h3>
                    <p class="mt-1 text-sm text-zinc-500">{{ $feature['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- FAQ --}}
    <div class="mx-auto max-w-3xl pb-16">
        <h2 class="text-center text-2xl font-bold text-zinc-900">{{ __('seo.landing_faq') }}</h2>
        <div class="mt-8 space-y-4">
            @foreach(range(1, 5) as $i)
                @php $qKey = "seo.landing_faq_q{$i}"; $aKey = "seo.landing_faq_a{$i}"; @endphp
                @if(__($qKey) !== $qKey)
                <details class="rounded-2xl border border-zinc-200 bg-white">
                    <summary class="cursor-pointer px-6 py-4 font-medium text-zinc-900">{{ __($qKey) }}</summary>
                    <p class="px-6 pb-4 text-sm text-zinc-600">{{ __($aKey) }}</p>
                </details>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Structured Data: FAQPage --}}
    @php
        $faqs = [];
        for ($i = 1; $i <= 5; $i++) {
            $q = __("seo.landing_faq_q{$i}");
            $a = __("seo.landing_faq_a{$i}");
            if ($q !== "seo.landing_faq_q{$i}") {
                $faqs[] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a]];
            }
        }
        // 注意：整个 schema 必须在 @php 块内构造后 json_encode 输出——
        // Blade 会把 HTML 部分里 JSON-LD 的 "@context"/"@type" 当作 @context/@type 指令解析，
        // 导致编译产物语法错误且结构化数据损坏
        $schema = $faqs !== []
            ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $faqs]
            : null;
    @endphp
    @if($schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    @endif
</div>
@endsection