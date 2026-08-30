@extends('layouts.app')

@section('title', 'API 文档')

@section('content')
<div class="container py-5">
    <h1 class="mb-4">API 文档</h1>
    <p class="lead">通过 REST API 访问您的网站分析数据。所有 API 端点需要 Bearer Token 认证。</p>

    <div class="card mb-4">
        <div class="card-header">
            <h4>认证方式</h4>
        </div>
        <div class="card-body">
            <p>所有 API 请求需在 Header 中携带 API Key：</p>
            <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>
            <p>您可以在 <a href="{{ route('account-api.index') }}">账户 → API</a> 页面生成和管理 API Key。</p>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>统计查询</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr><th>端点</th><th>方法</th><th>说明</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>/api/v1/websites/{id}/statistics</code></td><td>GET</td><td>统计聚合查询</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/realtime</code></td><td>GET</td><td>实时在线访客</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/pageviews-advanced</code></td><td>GET</td><td>高级页面浏览</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/pageviews-lightweight</code></td><td>GET</td><td>轻量页面浏览</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/visitors</code></td><td>GET</td><td>访客列表</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/visitors/{visitorId}</code></td><td>GET</td><td>单访客详情</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>目标与转化</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr><th>端点</th><th>方法</th><th>说明</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>/api/v1/websites/{id}/goals</code></td><td>GET</td><td>目标列表</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/goals-conversions</code></td><td>GET</td><td>目标转化记录</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>热图与回放</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr><th>端点</th><th>方法</th><th>说明</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>/api/v1/websites/{id}/heatmaps</code></td><td>GET</td><td>热图列表</td></tr>
                    <tr><td><code>/api/v1/websites/{id}/replays</code></td><td>GET</td><td>会话回放列表</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>网站管理</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr><th>端点</th><th>方法</th><th>说明</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>/api/v1/websites</code></td><td>GET</td><td>网站列表</td></tr>
                    <tr><td><code>/api/v1/websites</code></td><td>POST</td><td>创建网站</td></tr>
                    <tr><td><code>/api/v1/websites/{id}</code></td><td>GET</td><td>网站详情</td></tr>
                    <tr><td><code>/api/v1/websites/{id}</code></td><td>PUT</td><td>更新网站</td></tr>
                    <tr><td><code>/api/v1/websites/{id}</code></td><td>DELETE</td><td>删除网站</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h4>通用过滤参数</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead><tr><th>参数</th><th>类型</th><th>说明</th></tr></thead>
                <tbody>
                    <tr><td>start_date</td><td>string</td><td>开始日期 (YYYY-MM-DD)</td></tr>
                    <tr><td>end_date</td><td>string</td><td>结束日期 (YYYY-MM-DD)</td></tr>
                    <tr><td>path</td><td>string</td><td>页面路径过滤</td></tr>
                    <tr><td>referrer_host</td><td>string</td><td>来源域名过滤</td></tr>
                    <tr><td>utm_source</td><td>string</td><td>UTM 来源过滤</td></tr>
                    <tr><td>country_code</td><td>string</td><td>国家代码过滤</td></tr>
                    <tr><td>device_type</td><td>string</td><td>设备类型 (desktop/tablet/mobile)</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
