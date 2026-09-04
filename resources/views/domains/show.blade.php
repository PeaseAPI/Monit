@extends('layouts.app')
@section('content')
<div class="max-w-7xl">
    <div class="mb-6">
        <a href="{{ route('domains.index') }}" class="text-sm text-zinc-500 hover:underline">&larr; {{ __('msg.back_to_domains') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-zinc-900">{{ $domain->host }}</h1>
    </div>

    {{-- Basic Info --}}
    <div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-800 mb-4">{{ __('msg.domain_basic_info') }}</h2>
        <dl class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.domain_host') }}</dt>
                <dd class="mt-1 text-sm font-mono">{{ $domain->host }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.domain_scheme') }}</dt>
                <dd class="mt-1 text-sm">{{ $domain->scheme ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.domain_enabled') }}</dt>
                <dd class="mt-1 text-sm">
                    @if($domain->is_enabled)
                        <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">{{ __('msg.yes') }}</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">{{ __('msg.no') }}</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.domain_added') }}</dt>
                <dd class="mt-1 text-sm">{{ $domain->datetime?->format('Y-m-d H:i') ?? '—' }}</dd>
            </div>
        </dl>
    </div>

    {{-- WHOIS / Monitor Info --}}
    <div class="mb-6 rounded-2xl border border-zinc-200 bg-white p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-zinc-800">{{ __('msg.domain_whois_title') }}</h2>
            @if($domain->monitor_is_enabled)
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">{{ __('msg.monitoring_on') }}</span>
            @else
                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500">{{ __('msg.monitoring_off') }}</span>
            @endif
        </div>

        @if($domain->monitor_last_check_at)
            <p class="mb-4 text-xs text-zinc-400">{{ __('msg.last_checked') }}: {{ $domain->monitor_last_check_at->format('Y-m-d H:i') }}</p>
        @else
            <p class="mb-4 text-xs text-zinc-400">{{ __('msg.whois_not_checked_yet') }}</p>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.registrar') }}</dt>
                <dd class="mt-1 text-sm">{{ $domain->monitor_registrar ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.expiration_date') }}</dt>
                <dd class="mt-1 text-sm">
                    @if($domain->monitor_expiration_date)
                        {{ $domain->monitor_expiration_date->format('Y-m-d') }}
                        @php $days = now()->diffInDays($domain->monitor_expiration_date, false); @endphp
                        @if($days <= 0)
                            <span class="ml-2 inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">{{ __('domains.expired') }}</span>
                        @elseif($days < 30)
                            <span class="ml-2 inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">{{ __('msg.expiring_soon', ['days' => (int) $days]) }}</span>
                        @else
                            <span class="ml-2 inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">{{ __('msg.days_remaining', ['days' => (int) $days]) }}</span>
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.nameservers') }}</dt>
                <dd class="mt-1 text-sm">
                    @if($domain->monitor_nameservers)
                        @foreach(json_decode($domain->monitor_nameservers, true) ?? [] as $ns)
                            <span class="mr-2 inline-flex items-center rounded bg-zinc-100 px-2 py-0.5 text-xs font-mono">{{ $ns }}</span>
                        @endforeach
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium text-zinc-500">{{ __('msg.ssl_info') }}</dt>
                <dd class="mt-1 text-sm">
                    @if($domain->monitor_ssl)
                        @php $ssl = json_decode($domain->monitor_ssl, true) ?? []; @endphp
                        @if(!empty($ssl))
                            <dl class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <dt class="text-xs text-zinc-400">{{ __('msg.ssl_issuer') }}</dt>
                                    <dd class="text-sm">{{ $ssl['issuer'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-400">{{ __('msg.ssl_valid_from') }}</dt>
                                    <dd class="text-sm">{{ ($ssl['valid_from'] ?? null) ? \Carbon\Carbon::parse($ssl['valid_from'])->format('Y-m-d') : '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-zinc-400">{{ __('msg.ssl_valid_to') }}</dt>
                                    <dd class="text-sm">{{ ($ssl['valid_to'] ?? null) ? \Carbon\Carbon::parse($ssl['valid_to'])->format('Y-m-d') : '—' }}</dd>
                                </div>
                            </dl>
                        @else
                            —
                        @endif
                    @else
                        —
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <form method="POST" action="{{ route('domains.update') }}">
            @csrf @method('PUT')
            <input type="hidden" name="domain_id" value="{{ $domain->domain_id }}">
            <input type="hidden" name="monitor_is_enabled" value="{{ $domain->monitor_is_enabled ? 0 : 1 }}">
            <button type="submit" class="rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50">
                {{ $domain->monitor_is_enabled ? __('msg.disable_monitoring') : __('msg.enable_monitoring') }}
            </button>
        </form>
    </div>
</div>
@endsection
