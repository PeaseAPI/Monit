@extends('layouts.admin')
@section('title', __('admin.domain_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.domains.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.edit_domain') }} - {{ $domain->host }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.domains.update', $domain->domain_id) }}">@csrf @method('PUT')
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_user') }}</label>
            <select name="user_id" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required>
                @foreach($users as $id => $name)<option value="{{ $id }}" {{ old('user_id', $domain->user_id) == $id ? 'selected' : '' }}>{{ $name }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_scheme') }}</label>
            <select name="scheme" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="https" {{ old('scheme', $domain->scheme)==='https' ? 'selected' : '' }}>https</option><option value="http" {{ old('scheme', $domain->scheme)==='http' ? 'selected' : '' }}>http</option></select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_host') }}</label><input type="text" name="host" value="{{ old('host', $domain->host) }}" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_type') }}</label>
            <select name="type" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="0" {{ old('type', $domain->type)==0 ? 'selected' : '' }}>{{ __('admin.domain_type_user') }}</option><option value="1" {{ old('type', $domain->type)==1 ? 'selected' : '' }}>{{ __('admin.domain_type_platform') }}</option></select>
        </div>
        <div><label class="inline-flex items-center gap-2 text-sm text-zinc-700"><input type="checkbox" name="is_enabled" value="1" {{ old('is_enabled', $domain->is_enabled) ? 'checked' : '' }} class="h-4 w-4 rounded border-zinc-300 text-brand-600"> {{ __('common.enabled') }}</label></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.save') }}</button>
    </div>
    </form>
</div>
@endsection