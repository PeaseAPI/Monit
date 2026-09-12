@extends('layouts.app')
@section('title', __('tickets.new_ticket'))
@section('content')
@php
    $categoryIcons = [
        'general' => 'M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 01-.825-.242m9.345-8.334a2.126 2.126 0 00-.476-.095 48.64 48.64 0 00-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0011.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155',
        'technical' => 'M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z',
        'billing' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        'feature' => 'M9.813 15.904L9.5 17.25l-.313-1.346a4.5 4.5 0 00-2.59-2.59L5.25 13l1.346-.313a4.5 4.5 0 002.59-2.59L9.5 8.75l.313 1.346a4.5 4.5 0 002.59 2.59L13.75 13l-1.346.313a4.5 4.5 0 00-2.59 2.59zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.456-2.456L14.25 6l1.035-.259a3.375 3.375 0 002.456-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z',
        'abuse' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
    ];
@endphp
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('tickets.new_ticket') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('tickets.new_ticket_hint') }}</p>

    <form method="POST" action="{{ route('tickets.store') }}" class="mt-8 space-y-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        <div>
            <label for="subject" class="block text-sm font-semibold text-zinc-700">{{ __('tickets.subject_label') }}</label>
            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required maxlength="256" class="form-input mt-2" placeholder="{{ __('tickets.subject_placeholder') }}">
        </div>

        {{-- 分类：radio 卡片（纯 CSS peer-checked 高亮，无 JS） --}}
        <div>
            <span class="block text-sm font-semibold text-zinc-700">{{ __('tickets.category_label') }}</span>
            <div class="mt-2.5 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Models\Ticket::CATEGORIES as $category)
                <div class="relative">
                    <input type="radio" name="category" value="{{ $category }}" id="category-{{ $category }}" class="peer sr-only" @checked(old('category', 'general') === $category)>
                    <label for="category-{{ $category }}" class="flex cursor-pointer items-center gap-2.5 rounded-xl border border-zinc-200 px-3.5 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/30">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{!! $categoryIcons[$category] !!}"/></svg>
                        {{ __('tickets.category_'.$category) }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        {{-- 优先级：pill 单选 --}}
        <div>
            <span class="block text-sm font-semibold text-zinc-700">{{ __('tickets.priority_label') }}</span>
            <div class="mt-2.5 flex flex-wrap gap-2.5">
                @foreach (\App\Models\Ticket::PRIORITIES as $priority)
                <div class="relative">
                    <input type="radio" name="priority" value="{{ $priority }}" id="priority-{{ $priority }}" class="peer sr-only" @checked(old('priority', 'normal') === $priority)>
                    <label for="priority-{{ $priority }}" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/30">
                        <span class="h-1.5 w-1.5 rounded-full {{ $priority === 'high' ? 'bg-red-500' : ($priority === 'low' ? 'bg-zinc-400' : 'bg-sky-500') }}"></span>
                        {{ __('tickets.priority_'.$priority) }}
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <div>
            <label for="message" class="block text-sm font-semibold text-zinc-700">{{ __('tickets.message_label') }}</label>
            <textarea name="message" id="message" rows="8" required class="form-input mt-2" placeholder="{{ __('tickets.message_placeholder') }}">{{ old('message') }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-4 border-t border-zinc-100 pt-5">
            <a href="{{ route('tickets.index') }}" class="text-sm font-medium text-zinc-500 transition hover:text-zinc-700">{{ __('common.cancel') }}</a>
            <button class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                {{ __('tickets.submit') }}
            </button>
        </div>
    </form>
</div>
@endsection

