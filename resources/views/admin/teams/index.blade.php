@extends('layouts.admin')
@section('title', __('admin.teams'))
@section('content')
<div class="mb-6"><h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.teams') }}</h1></div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.name') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.owner') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.members_count') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.datetime') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($teams as $t)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $t->team_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $t->name }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ $t->owner?->email ?? $t->user_id }}</td>
            <td class="px-6 py-3"><a href="{{ route('admin.teams.members', $t->team_id) }}" class="text-brand-600 hover:text-brand-700">{{ $t->members_count }}</a></td>
            <td class="px-6 py-3 text-zinc-500">{{ $t->datetime?->format('Y-m-d H:i') }}</td>
            <td class="px-6 py-3 text-right">
                <a href="{{ route('admin.teams.members', $t->team_id) }}" class="mr-3 text-sm text-brand-600 hover:text-brand-700">{{ __('admin.view_members') }}</a>
                <form method="POST" action="{{ route('admin.teams.destroy', $t->team_id) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $teams->links() }}
@endsection
