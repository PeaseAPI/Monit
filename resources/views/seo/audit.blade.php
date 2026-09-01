@extends('layouts.guest')
@section('content')
<div class="max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="max-w-2xl truncate text-2xl font-bold text-zinc-900">{{ $audit->url }}</h1>
        <span class="rounded-full px-3 py-1 text-sm font-semibold {{ ['poor' => 'bg-red-50 text-red-700', 'decent' => 'bg-yellow-50 text-yellow-700', 'good' => 'bg-emerald-50 text-emerald-700'][$audit->band] }}">{{ $audit->score }}/100 · {{ $audit->passed_tests }}/{{ $audit->total_tests }}</span>
    </div>
    <p class="mt-1 text-sm text-zinc-500">{{ __('seo.response_time') }}: {{ $audit->response_time_ms }} ms · {{ __('seo.page_size') }}: {{ number_format($audit->page_size_bytes / 1024, 1) }} KB · {{ $audit->created_at?->format('Y-m-d H:i') }}</p>

    @if(auth()->check() && (int)$audit->user_id === (int)auth()->user()->user_id)
        <form method="POST" action="{{ route('seo.audits.share', $audit->seo_audit_id) }}" class="mt-4 flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4 text-sm">
            @csrf
            <span class="font-medium text-zinc-700">{{ __('seo.share') }}:</span>
            <select name="privacy" class="rounded-lg border border-zinc-200 px-2 py-1">
                @foreach(['public' => __('seo.public'), 'private' => __('seo.private'), 'password' => __('seo.password')] as $value => $label)
                    <option value="{{ $value }}" @selected($audit->privacy === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="password" placeholder="{{ __('seo.share_password_hint') }}" class="w-40 rounded-lg border border-zinc-200 px-2 py-1">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_public_directory" value="1" @checked($audit->is_public_directory)> {{ __('seo.list_in_directory') }}</label>
            <button class="rounded-lg bg-zinc-900 px-3 py-1.5 text-white">{{ __('common.save') }}</button>
        </form>
    @endif

    @if($audit->ai_summary)
        <div class="mt-6 rounded-2xl border border-indigo-100 bg-indigo-50 p-4 text-sm leading-6 text-indigo-900">{{ $audit->ai_summary }}</div>
    @endif

    @foreach($grouped as $category => $rows)
        <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 bg-white">
            <div class="flex items-center justify-between bg-zinc-50 px-6 py-3">
                <h2 class="font-semibold text-zinc-800">{{ __("seo.category_{$category}") }}</h2>
                <span class="text-sm text-zinc-500">{{ $audit->category_scores[$category] ?? '-' }}/100</span>
            </div>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-zinc-100">
                @foreach($rows as $key => $row)
                    <tr>
                        <td class="px-6 py-3 w-24">
                            <span class="rounded-full px-2 py-1 text-xs {{ ($row['passed'] ?? false) ? 'bg-emerald-50 text-emerald-700' : ($row['importance'] === 'major' ? 'bg-red-50 text-red-700' : 'bg-yellow-50 text-yellow-700') }}">{{ ($row['passed'] ?? false) ? __('seo.pass') : __('seo.fail') }}</span>
                        </td>
                        <td class="px-2 py-3 text-zinc-700">{{ \Illuminate\Support\Str::headline($key) }}</td>
                        <td class="px-6 py-3 text-right text-zinc-500">{{ $row['value'] ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
