@extends('layouts.app', ['nav' => 'dashboard'])
@section('title', __('msg.dashboard_views'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('msg.dashboard_views') }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('msg.dashboard_views_desc') }}</p>
    </div>
    <button type="button" onclick="document.getElementById('createViewForm').classList.toggle('hidden')" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">
        {{ __('msg.create_view') }}
    </button>
</div>

<div id="createViewForm" class="mb-6 hidden rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('dashboard-views.store') }}">
        @csrf
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-zinc-700">{{ __('msg.view_name') }}</label>
            <input type="text" name="name" id="name" class="form-input" required>
        </div>
        <div class="mb-4">
            <label for="order" class="block text-sm font-medium text-zinc-700">{{ __('msg.view_order') }}</label>
            <input type="number" name="order" id="order" class="form-input" value="0">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700">{{ __('msg.view_settings') }}</label>
            <textarea name="settings" rows="4" class="mt-1 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm font-mono" placeholder='{"widgets": []}'></textarea>
        </div>
        <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800">{{ __('msg.save') }}</button>
    </form>
</div>

<div class="space-y-4">
    @forelse($views as $view)
    <div class="rounded-2xl border border-zinc-200 bg-white p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900">{{ $view->name }}</h3>
                <p class="text-sm text-zinc-500">{{ __('msg.order') }}: {{ $view->order }} &middot; {{ $view->datetime?->format('Y-m-d H:i') }}</p>
            </div>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('dashboard-views.update', $view->dashboard_view_id ?? $view->id) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="name" value="{{ $view->name }}">
                    <button type="submit" class="rounded-lg bg-zinc-100 px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-200">{{ __('msg.edit') }}</button>
                </form>
                <form method="POST" action="{{ route('dashboard-views.destroy', $view->dashboard_view_id ?? $view->id) }}" onsubmit="return confirm('{{ __('msg.confirm_delete_view') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 text-sm text-red-600 hover:bg-red-100">{{ __('msg.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="rounded-2xl border border-zinc-200 bg-white p-12 text-center">
        <p class="text-zinc-500">{{ __('msg.no_dashboard_views') }}</p>
    </div>
    @endforelse
</div>
@endsection
