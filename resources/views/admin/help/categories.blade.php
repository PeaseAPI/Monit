@extends('layouts.admin')
@section('title', __('admin.help_categories'))
@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.help_categories') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('admin.help_categories_hint') }}</p>
</div>

<div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-6">
    <h2 class="mb-4 text-sm font-semibold text-zinc-900">+ {{ __('admin.category_new') }}</h2>
    <form method="POST" action="{{ route('admin.help-categories.store') }}" class="flex flex-wrap items-end gap-3">
        @csrf
        <div><label class="mb-1 block text-xs text-zinc-500">{{ __('admin.title') }}</label>
            <input type="text" name="title" required maxlength="64" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm"></div>
        <div><label class="mb-1 block text-xs text-zinc-500">URL</label>
            <input type="text" name="url" required maxlength="256" pattern="[a-z0-9-]+" class="rounded-xl border border-zinc-300 px-3 py-2 text-sm font-mono"></div>
        <div><label class="mb-1 block text-xs text-zinc-500">{{ __('admin.order') }}</label>
            <input type="number" name="order" min="0" max="9999" value="0" class="w-24 rounded-xl border border-zinc-300 px-3 py-2 text-sm"></div>
        <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.create') }}</button>
    </form>
</div>

<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.title') }}</th><th class="px-6 py-3 font-medium text-zinc-500">URL</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.order') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($categories as $c)
        <tr data-row="{{ $c->category_id }}">
            <td class="px-6 py-3 text-zinc-500">{{ $c->category_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900"><span data-view="title">{{ $c->title }}</span><input type="text" data-edit="title" value="{{ $c->title }}" maxlength="64" class="hidden w-40 rounded-lg border border-zinc-300 px-2 py-1 text-sm"></td>
            <td class="px-6 py-3 font-mono text-xs text-zinc-500"><span data-view="url">{{ $c->url }}</span><input type="text" data-edit="url" value="{{ $c->url }}" pattern="[a-z0-9-]+" class="hidden w-40 rounded-lg border border-zinc-300 px-2 py-1 text-sm font-mono"></td>
            <td class="px-6 py-3 text-zinc-700"><span data-view="order">{{ $c->order }}</span><input type="number" data-edit="order" value="{{ $c->order }}" min="0" max="9999" class="hidden w-20 rounded-lg border border-zinc-300 px-2 py-1 text-sm"></td>
            <td class="px-6 py-3 text-zinc-500">{{ $c->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <button type="button" data-edit-toggle="{{ $c->category_id }}" class="text-sm text-brand-600 hover:text-brand-700">{{ __('common.edit') }}</button>
                <form method="POST" action="{{ route('admin.help-categories.update', $c->category_id) }}" data-edit-form="{{ $c->category_id }}" class="hidden inline">@csrf @method('PUT')
                    <button class="text-sm text-brand-600 hover:text-brand-700">{{ __('common.save') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.help-categories.destroy', $c->category_id) }}" class="inline" data-confirm="{{ __('common.confirm_delete') }}">@csrf @method('DELETE')
                    <button class="text-sm text-red-500 hover:text-red-700">{{ __('common.delete') }}</button>
                </form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>

{{-- 行内编辑切换（title/url/order 原位编辑后整行提交） --}}
@if ($categories->isNotEmpty())
<script>
    document.addEventListener('click', function (e) {
        var toggle = e.target.closest('[data-edit-toggle]');
        if (toggle) {
            var id = toggle.getAttribute('data-edit-toggle');
            var row = document.querySelector('[data-row="' + id + '"]');
            row.querySelectorAll('[data-view]').forEach(function (el) { el.classList.add('hidden'); });
            row.querySelectorAll('[data-edit]').forEach(function (el) { el.classList.remove('hidden'); });
            toggle.classList.add('hidden');
            row.querySelector('[data-edit-form="' + id + '"]').classList.remove('hidden');
        }
    });
</script>
@endif
@endsection
