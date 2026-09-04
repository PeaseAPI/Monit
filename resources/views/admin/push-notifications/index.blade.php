@extends('layouts.admin')
@section('title', __('admin.push_campaigns'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.push_campaigns') }}</h1>
    <a href="{{ route('admin.push-notifications.create') }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 text-left"><tr>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.campaign_name') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.campaign_title') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.campaign_status') }}</th>
                <th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.col_date') }}</th>
                <th class="px-6 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse($campaigns as $campaign)
                <tr>
                    <td class="px-6 py-3 font-medium text-zinc-900">{{ $campaign->name }}</td>
                    <td class="px-6 py-3 text-zinc-500">{{ $campaign->title }}</td>
                    <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $campaign->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $campaign->status === 'sent' ? __('admin.status_sent') : __('admin.status_pending') }}</span></td>
                    <td class="px-6 py-3 text-zinc-500">{{ $campaign->datetime }}</td>
                    <td class="px-6 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.push-notifications.edit', $campaign) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                        <form method="POST" action="{{ route('admin.push-notifications.destroy', $campaign) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" data-confirm="{{ __('common.confirm_delete') }}" onclick="return confirm(this.dataset.confirm)">{{ __('common.delete') }}</button></form>
                    </td>
                </tr>
                @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="5">{{ __('common.no_data') }}</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection