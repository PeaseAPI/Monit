@extends('layouts.admin')
@section('title', 'Push Notifications')
@section('content')
<div class="mb-6">
    <a href="{{ route('admin.plugins.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('plugins.back_to_list') }}</a>
    <h1 class="mt-2 text-2xl font-bold text-zinc-900">🔔 Push Notifications — {{ __('plugins.push.campaign_mgmt') }}</h1>
    <p class="mt-1 text-sm text-zinc-500">{{ __('plugins.push.subscribers') }} {{ number_format($subscribersTotal) }}</p>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-zinc-200 bg-white p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('plugins.push.campaign_list') }}</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-zinc-200 text-left text-zinc-500">
                    <th class="py-2 pr-4">{{ __('plugins.push.col_name') }}</th><th class="py-2 pr-4">{{ __('plugins.push.col_website') }}</th><th class="py-2 pr-4">{{ __('plugins.push.col_status') }}</th><th class="py-2 pr-4">{{ __('plugins.push.col_sent') }}</th><th class="py-2"></th>
                </tr></thead>
                <tbody>
                @forelse($campaigns as $c)
                    <tr class="border-b border-zinc-100">
                        <td class="py-2 pr-4 font-medium text-zinc-800">{{ $c->name }}</td>
                        <td class="py-2 pr-4 text-zinc-600">{{ optional($c->website)->host }}</td>
                        <td class="py-2 pr-4">
                            @if($c->is_sent)
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">{{ __('plugins.push.status_sent') }}</span>
                            @elseif($c->is_enabled)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">{{ __('plugins.push.status_pending') }}</span>
                            @else
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500">{{ __('plugins.push.status_disabled') }}</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4 text-zinc-600">{{ $c->total_sent }} ✓ / {{ $c->total_failed }} ✗</td>
                        <td class="py-2 text-right">
                            @unless($c->is_sent)
                                <form method="POST" action="{{ route('admin.plugins.push-notifications.campaigns.send', $c->campaign_id) }}" class="inline"
                                      onsubmit="return confirm('{{ __("plugins.push.confirm_send") }}')">@csrf
                                    <button class="mr-2 text-sm text-brand-600 hover:underline">{{ __('plugins.push.send') }}</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('admin.plugins.push-notifications.campaigns.destroy', $c->campaign_id) }}" class="inline"
                                  onsubmit="return confirm('{{ __("plugins.push.confirm_delete") }}')">@csrf @method('DELETE')
                                <button class="text-sm text-red-600 hover:underline">{{ __('plugins.push.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-zinc-400">{{ __('plugins.push.no_campaigns') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-6">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('plugins.push.create_campaign') }}</h2>
            <form method="POST" action="{{ route('admin.plugins.push-notifications.campaigns.store') }}" class="mt-4 space-y-3">@csrf
                <input type="number" name="website_id" placeholder="Website ID" required class="form-input">
                <input type="text" name="name" placeholder="{{ __('plugins.push.placeholder_name') }}" required class="form-input">
                <input type="text" name="title" placeholder="{{ __('plugins.push.placeholder_title') }}" required class="form-input">
                <textarea name="description" placeholder="{{ __('plugins.push.placeholder_body') }}" class="form-input" rows="2"></textarea>
                <input type="url" name="url" placeholder="{{ __('plugins.push.placeholder_url') }}" class="form-input">
                <button class="w-full rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">{{ __('plugins.push.create') }}</button>
            </form>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6">
            <h2 class="text-lg font-semibold text-zinc-900">{{ __('plugins.push.vapid_keys') }}</h2>
            <p class="mt-2 text-sm text-zinc-500">{{ __('plugins.push.vapid_desc') }}</p>
            <form method="POST" action="{{ route('admin.plugins.push-notifications.generate-keys') }}" class="mt-4">@csrf
                <button class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50">{{ __('plugins.push.generate_keys') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
