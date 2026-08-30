@extends('layouts.admin')
@section('title', __('admin.team_members').' - '.$team->name)
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900">{{ __('admin.team_members') }}：{{ $team->name }}</h1>
        <p class="mt-1 text-sm text-zinc-500">{{ __('admin.owner') }}：{{ $team->owner?->email ?? $team->user_id }}</p>
    </div>
    <a href="{{ route('admin.teams.index') }}" class="rounded-xl border border-zinc-300 px-4 py-2.5 text-sm text-zinc-700 hover:bg-zinc-50">← {{ __('admin.teams') }}</a>
</div>
<div class="rounded-2xl border border-zinc-200 bg-white"><div class="overflow-x-auto">
    <table class="w-full text-sm"><thead class="bg-zinc-50 text-left"><tr><th class="px-6 py-3 font-medium text-zinc-500">ID</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.email') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.access') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('common.status') }}</th><th class="px-6 py-3 font-medium text-zinc-500">{{ __('admin.last_activity') }}</th><th class="px-6 py-3"></th></tr></thead>
    <tbody class="divide-y divide-zinc-100">
        @forelse($members as $m)
        <tr>
            <td class="px-6 py-3 text-zinc-500">{{ $m->team_member_id }}</td>
            <td class="px-6 py-3 font-medium text-zinc-900">{{ $m->user_email }}</td>
            <td class="px-6 py-3 text-zinc-700">{{ is_array($m->access) ? implode(', ', $m->access) : ($m->access ?: '—') }}</td>
            <td class="px-6 py-3"><span class="rounded-full px-2 py-0.5 text-xs {{ $m->status === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-500' }}">{{ $m->status === 1 ? __('admin.member_active') : __('admin.member_invited') }}</span></td>
            <td class="px-6 py-3 text-zinc-500">{{ $m->last_activity?->format('Y-m-d H:i') ?: '—' }}</td>
            <td class="px-6 py-3 text-right">
                <form method="POST" action="{{ route('admin.teams.members.destroy', [$m->team_id, $m->team_member_id]) }}" class="inline">@csrf @method('DELETE')<button class="text-sm text-red-500 hover:text-red-700" onclick="return confirm('{{ __('common.confirm_delete') }}')">{{ __('common.delete') }}</button></form>
            </td>
        </tr>
        @empty<tr><td class="px-6 py-8 text-center text-zinc-500" colspan="6">{{ __('common.no_data') }}</td></tr>@endforelse
    </tbody></table>
</div></div>
{{ $members->links() }}
@endsection
