@extends('layouts.public')
@section('title', __('api_docs.title'))

{{-- API 文档（M27 重写）：完整端点清单 + 认证示例 + 错误码 + 帮助中心/工单关联 --}}
@section('content')
<div class="mx-auto max-w-5xl">
    <h1 class="text-3xl font-bold text-zinc-900">{{ __('api_docs.title') }}</h1>
    <p class="mt-2 text-sm text-zinc-600">{{ __('api_docs.intro') }}</p>

    {{-- 快速锚点导航 --}}
    <div class="mt-6 flex flex-wrap gap-2 text-sm">
        <a href="#auth" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.auth') }}</a>
        <a href="#account" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_account') }}</a>
        <a href="#websites" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_websites') }}</a>
        <a href="#analytics" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_analytics') }}</a>
        <a href="#resources" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_resources') }}</a>
        <a href="#public" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_public') }}</a>
        <a href="#admin" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.grp_admin') }}</a>
        <a href="#errors" class="rounded-full bg-zinc-100 px-4 py-1.5 font-medium text-zinc-700 transition hover:bg-brand-50 hover:text-brand-700">{{ __('api_docs.errors') }}</a>
    </div>

    {{-- 认证 --}}
    <div id="auth" class="mt-8 rounded-2xl border border-zinc-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.auth') }}</h2>
        <p class="mt-2 text-sm text-zinc-600">{{ __('api_docs.auth_desc') }}</p>
        <pre class="mt-4 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-xs leading-relaxed text-zinc-100"><code>curl https://{{ request()->getHost() }}/api/v1/user \
  -H "Authorization: Bearer YOUR_API_KEY"</code></pre>
        <p class="mt-3 text-sm text-zinc-600">{{ __('api_docs.base_url') }}: <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">https://{{ request()->getHost() }}/api/v1</code></p>
        <p class="mt-2 text-sm text-zinc-600">{{ __('api_docs.get_key') }} <a href="{{ route(auth()->check() ? 'account.index' : 'login') }}" class="font-medium text-brand-600 hover:underline">{{ __('api_docs.get_key_link') }}</a></p>
    </div>

    {{-- 账户 --}}
    <div id="account" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_account') }}</h2>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/user</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_user') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/logs</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_logs') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/payments</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_payments') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/dashboard-views[/{view}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_dashboard_views') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/teams[/{team}][/members[/{memberId}]]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_teams') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/plans</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_plans') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- 网站管理 --}}
    <div id="websites" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_websites') }}</h2>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites[/{website}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_websites') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/domains · /api/v1/domains/{domainId}</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_domains') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 数据分析 --}}
    <div id="analytics" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_analytics') }}</h2>
        <p class="mt-1 text-xs text-zinc-500">{{ __('api_docs.own_note') }}</p>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/realtime</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_realtime') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/visitors</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_visitors') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/events</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_events') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/metrics</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_metrics') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/top-pages · top-referrers · top-countries · top-browsers · top-devices · top-operating-systems</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_tops') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/statistics</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_statistics') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/pageviews-advanced · pageviews-lightweight</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_pageviews') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/sessions[/list][/{sessionId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_sessions') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/replays[/list][/{replayId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_replays') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/visitors/list · visitors/{visitorId}</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_visitors_detail') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/utm · goals</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_utm') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- 资源管理 --}}
    <div id="resources" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_resources') }}</h2>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/goals[/list][/{goal}[/detail]]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_goals') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/heatmaps[/{heatmap}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_heatmaps') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/annotations · /api/v1/websites/{website}/annotations[/{annotation}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_annotations') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/events-children[/list]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_events_children') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/outbound-clicks[/list]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_outbound') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/websites/{website}/goals-conversions[/{conversionId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_conversions') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/logs/list · /api/v1/payments/list</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_lists') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 公开采集 --}}
    <div id="public" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_public') }}</h2>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">POST</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/public/track</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_track') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- 管理员 --}}
    <div id="admin" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.grp_admin') }}</h2>
        <p class="mt-1 text-xs text-zinc-500">{{ __('api_docs.admin_note') }}</p>
        <div class="mt-3 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.method') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.endpoint') }}</th><th class="px-4 py-2.5 font-semibold">{{ __('api_docs.desc_col') }}</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-600">
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/status · /statistics</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_status') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/users[/{userId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_users') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/websites[/{websiteId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_websites') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/POST/PUT/DELETE</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/plans[/{planId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_plans') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">GET</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/payments[/{paymentId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_payments') }}</td></tr>
                    <tr><td class="px-4 py-2.5"><span class="rounded bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">GET/PUT</span></td><td class="px-4 py-2.5"><code class="text-xs">/api/v1/admin/settings · /plugins[/{pluginId}]</code></td><td class="px-4 py-2.5">{{ __('api_docs.ep_admin_settings') }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    {{-- 错误码 --}}
    <div id="errors" class="mt-8">
        <h2 class="text-lg font-semibold text-zinc-900">{{ __('api_docs.errors') }}</h2>
        <div class="mt-3 grid gap-3 text-sm text-zinc-600 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">401 Unauthorized</b><p class="mt-1 text-xs">{{ __('api_docs.err_401') }}</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">403 Forbidden</b><p class="mt-1 text-xs">{{ __('api_docs.err_403') }}</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">404 Not Found</b><p class="mt-1 text-xs">{{ __('api_docs.err_404') }}</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">422 Unprocessable</b><p class="mt-1 text-xs">{{ __('api_docs.err_422') }}</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">429 Too Many</b><p class="mt-1 text-xs">{{ __('api_docs.err_429') }}</p></div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4"><b class="text-zinc-900">500 Server Error</b><p class="mt-1 text-xs">{{ __('api_docs.err_500') }}</p></div>
        </div>
    </div>

    {{-- 帮助中心 / 工单关联 --}}
    <div class="mt-10 rounded-2xl bg-gradient-to-r from-brand-600 to-indigo-600 p-6 text-white sm:flex sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold">{{ __('api_docs.more_help') }}</h2>
            <p class="mt-1 text-sm text-brand-100">{{ __('api_docs.more_help_desc') }}</p>
        </div>
        <div class="mt-4 flex gap-3 sm:mt-0">
            <a href="{{ route('help') }}" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('api_docs.help_center') }}</a>
            @auth
            <a href="{{ route('tickets.create') }}" class="rounded-xl border border-white/40 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">{{ __('api_docs.ticket') }}</a>
            @endauth
        </div>
    </div>
</div>
@endsection
