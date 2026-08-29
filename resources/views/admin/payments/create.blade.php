@extends('layouts.admin')
@section('title', __('admin.payment_list'))
@section('content')
<div class="mb-6"><a href="{{ route('admin.payments.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('common.back') }}</a><h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ 'New Payment' }}</h1></div>
<div class="max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
    <form method="POST" action="{{ route('admin.payments.store') }}">@csrf
    <div class="space-y-4">
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.user_name') }}</label>
            <select name="user_id" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required>
                @foreach($users as $uid => $uname)<option value="{{ $uid }}">{{ $uname }}</option>@endforeach
            </select>
        </div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.plan_price_col') }}</label><input type="number" step="0.01" name="total_amount" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ 'Currency' }}</label><select name="currency" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="CNY">CNY</option><option value="USD">USD</option></select></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ 'Payment Processor' }}</label><input type="text" name="payment_processor" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm" required></div>
        <div><label class="block text-sm font-medium text-zinc-700">{{ 'Type' }}</label><select name="type" class="mt-1 w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm"><option value="one_time">{{ __('common.add') }}</option><option value="subscription">{{ 'Subscription' }}</option></select></div>
        <button class="rounded-xl bg-brand-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('common.add') }}</button>
    </div></form>
</div>
@endsection