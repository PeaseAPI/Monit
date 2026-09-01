@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <x-stats-header :website="$website" :title="__('stats.referral_categories_title')" />
    <x-range-switcher :route-name="'stats.referral_categories'" :website="$website" :range="$range" />

    {{-- M22 AI 引荐（ChatGPT/Claude/Perplexity/Copilot/Gemini） --}}
    <div class="mt-6">
        <x-rank-panel :title="__('stats.ai_referrers')" :items="$ai" :show-rank="true" />
    </div>

    {{-- M22 社交媒体引荐 --}}
    <div class="mt-4">
        <x-rank-panel :title="__('stats.social_media_referrers')" :items="$social" :show-rank="true" />
    </div>

    {{-- M22 搜索引擎引荐 --}}
    <div class="mt-4">
        <x-rank-panel :title="__('stats.search_engine_referrers')" :items="$search" :show-rank="true" />
    </div>
</div>
@endsection
