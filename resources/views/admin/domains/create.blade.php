@extends('layouts.admin')
@section('title', __('admin.domain_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.domains.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ __('admin.create_domain') }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.domains.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_user') }}</label>
            <select name="user_id" class="form-input" required>
                @foreach($users as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_scheme') }}</label>
            <select name="scheme" class="form-input"><option value="https">https</option><option value="http">http</option></select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_host') }}</label><input type="text" name="host" class="form-input" required placeholder="custom.example.com"></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.domain_type') }}</label>
            <select name="type" class="form-input"><option value="0">{{ __('admin.domain_type_user') }}</option><option value="1">{{ __('admin.domain_type_platform') }}</option></select>
        </div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div>
    </form>
</div>
@endsection