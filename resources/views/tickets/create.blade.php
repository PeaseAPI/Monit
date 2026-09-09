@extends('layouts.app')
@section('title', __('tickets.new_ticket'))
@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('tickets.new_ticket') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('tickets.new_ticket_hint') }}</p>

    <form method="POST" action="{{ route('tickets.store') }}" class="mt-8 space-y-5 rounded-2xl border border-zinc-200 bg-white p-6">
        @csrf
        <div>
            <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.subject_label') }}</label>
            <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="256" class="form-input mt-1.5" placeholder="{{ __('tickets.subject_placeholder') }}">
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.category_label') }}</label>
                <select name="category" class="form-input mt-1.5">
                    @foreach(\App\Models\Ticket::CATEGORIES as $category)
                    <option value="{{ $category }}" @selected(old('category', 'general'))>{{ __('tickets.category_'.$category) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.priority_label') }}</label>
                <select name="priority" class="form-input mt-1.5">
                    @foreach(\App\Models\Ticket::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(old('priority', 'normal'))>{{ __('tickets.priority_'.$priority) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-zinc-700">{{ __('tickets.message_label') }}</label>
            <textarea name="message" rows="8" required class="form-input mt-1.5" placeholder="{{ __('tickets.message_placeholder') }}">{{ old('message') }}</textarea>
        </div>
        <div class="flex items-center gap-3">
            <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('tickets.submit') }}</button>
            <a href="{{ route('tickets.index') }}" class="text-sm text-zinc-500 hover:text-zinc-700">{{ __('common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection
