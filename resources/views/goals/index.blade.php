@extends('layouts.app')
@section('title', __('stats.goals'))
@section('content')
<div class="py-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('stats.goals') }}</h1>
        <a href="{{ route('goals.create', $website) }}" class="rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700">+ {{ __('common.add') }}</a>
    </div>

    <div class="rounded-2xl border border-zinc-200 bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 text-left"><tr>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_name') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_type') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.goal_path') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('stats.conversions') }}</th>
                    <th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th>
                    <th class="px-6 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-zinc-100">
                    @forelse($goals as $goal)
                    <tr>
                        <td class="px-6 py-3 font-medium text-zinc-900">{{ $goal->name }}</td>
                        <td class="px-6 py-3 text-zinc-500">{{ $goal->type }}</td>
                        <td class="px-6 py-3 text-zinc-500 font-mono text-xs">{{ $goal->path ?? $goal->url ?? '-' }}</td>
                        <td class="px-6 py-3 text-zinc-700 font-medium">{{ $goal->conversions_count ?? $goal->conversions()->count() }}</td>
                        <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $goal->is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $goal->is_enabled ? __('msg.status_enabled') : __('msg.status_disabled') }}</span></td>
                        <td class="px-6 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('goals.edit', [$website, $goal]) }}" class="mr-3 text-sm text-zinc-500 hover:text-brand-600">{{ __('common.edit') }}</a>
                            <form method="POST" action="{{ route('goals.destroy', [$website, $goal]) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
                        </td>
                    </tr>
                    @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection