@extends('layouts.app', ['nav' => 'dashboard'])

@section('title', '安装代码')

@section('content')
    @php
        $pixelUrl = url('/assets/pixel/monit.js');
        $snippet = '<script src="' . $pixelUrl . '" data-monit="' . $website->pixel_key . '" async></script>';
    @endphp

    <div class="mx-auto max-w-3xl">
        <h2 class="text-2xl font-bold">安装统计代码</h2>
        <p class="mt-2 text-sm text-zinc-500">
            将以下代码添加到 <strong>{{ $website->host }}</strong> 每个页面的 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">&lt;head&gt;</code> 标签内（或网站模板的公共头部）。
        </p>

        {{-- 模式说明 --}}
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border {{ $website->isLightweight() ? 'border-amber-300 bg-amber-50' : 'border-zinc-200 bg-white' }} p-5 shadow-sm">
                <p class="text-sm font-semibold {{ $website->isLightweight() ? 'text-amber-700' : 'text-zinc-700' }}">
                    当前模式：{{ $website->isLightweight() ? '轻量模式' : '完整模式' }}
                </p>
                <p class="mt-2 text-sm text-zinc-600">
                    @if ($website->isLightweight())
                        仅记录基础浏览数据（页面、来源、设备、地区），存储占用小，适合高流量站点。
                    @else
                        记录完整事件流（页面、点击、事件、目标转化），支持回放与事件分析。
                    @endif
                    可在「网站管理」中随时切换。
                </p>
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-zinc-700">像素密钥</p>
                <code class="mt-2 block truncate rounded-lg bg-zinc-950 px-3 py-2 text-sm text-emerald-400">{{ $website->pixel_key }}</code>
                <p class="mt-2 text-xs text-zinc-400">用于标识你的网站，请勿泄露。</p>
            </div>
        </div>

        {{-- 安装代码 --}}
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-700">嵌入代码</h3>
                <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('snippet').textContent).then(()=>{this.textContent='已复制 ✓';setTimeout(()=>this.textContent='复制代码',1500)})"
                        class="rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">
                    复制代码
                </button>
            </div>
            <pre id="snippet" class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm leading-relaxed text-zinc-100"><code>{{ $snippet }}</code></pre>
        </div>

        {{-- 高级用法 --}}
        <div class="mt-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-zinc-700">高级用法（SPA / 自定义事件）</h3>
            <pre class="mt-3 overflow-x-auto rounded-xl bg-zinc-950 p-4 text-sm leading-relaxed text-zinc-300"><code>// SPA 路由切换时手动上报
Monit.pageview();

// 触发目标转化（goal_key 需与后台配置一致）
Monit.goalConversion('signup');</code></pre>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('dashboard', ['website_id' => $website->website_id]) }}"
               class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                返回仪表盘
            </a>
            <a href="{{ route('websites.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-700">
                网站管理
            </a>
        </div>
    </div>
@endsection
