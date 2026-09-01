@extends('layouts.app')
@section('content')
<div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-zinc-900">{{ __('account.api_key_management') }}</h1>
    <p class="mt-2 text-sm text-zinc-500">{{ __('account.api_key_desc') }}</p>

    {{-- 当前 API 密钥 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.api_key') }}</h3>

        @if($user->api_key)
            <div class="mt-3 flex items-center gap-3">
                <code class="flex-1 rounded-xl bg-zinc-100 px-4 py-2.5 text-xs text-zinc-700 font-mono break-all select-all">{{ $user->api_key }}</code>
                <button type="button" onclick="navigator.clipboard.writeText('{{ $user->api_key }}')" class="rounded-xl border border-zinc-300 px-3 py-2 text-xs font-medium text-zinc-600 hover:bg-zinc-50">
                    {{ __('account.copy') }}
                </button>
            </div>

            <div class="mt-4 flex gap-3">
                {{-- 重新生成 --}}
                <form method="POST" action="{{ route('account-api.regenerate') }}">
                    @csrf @method('PUT')
                    <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700" onclick="return confirm('{{ __('account.confirm_regenerate') }}')">
                        {{ __('account.regenerate_api_key') }}
                    </button>
                </form>

                {{-- 吊销 --}}
                <form method="POST" action="{{ route('account-api.revoke') }}">
                    @csrf @method('DELETE')
                    <button class="rounded-xl border border-red-300 px-5 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50" onclick="return confirm('{{ __('account.confirm_revoke') }}')">
                        {{ __('account.revoke_api_key') }}
                    </button>
                </form>
            </div>
        @else
            <p class="mt-3 text-sm text-zinc-500">{{ __('account.no_api_key') }}</p>
            <form method="POST" action="{{ route('account-api.regenerate') }}" class="mt-4">
                @csrf @method('PUT')
                <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700">
                    {{ __('account.generate_api_key') }}
                </button>
            </form>
        @endif
    </div>

    {{-- API 文档链接 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.api_documentation') }}</h3>
        <p class="mt-2 text-sm text-zinc-500">{{ __('account.api_documentation_desc') }}</p>
        <a href="{{ route('api.docs') }}" class="mt-3 inline-block rounded-xl bg-zinc-100 px-5 py-2.5 text-sm font-medium text-zinc-700 hover:bg-zinc-200">
            {{ __('account.view_api_docs') }} &rarr;
        </a>
    </div>

    {{-- 使用示例 --}}
    <div class="mt-6 max-w-xl rounded-2xl border border-zinc-200 bg-white p-6">
        <h3 class="text-sm font-semibold text-zinc-900">{{ __('account.usage_example') }}</h3>
        <pre class="mt-3 overflow-x-auto rounded-xl bg-zinc-900 p-4 text-xs text-green-400"><code>curl -H "Authorization: Bearer {{ $user->api_key ?? 'YOUR_API_KEY' }}" \
     {{ route('api.v1.user') }}</code></pre>
    </div>
</div>
@endsection
