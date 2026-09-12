@extends('layouts.public')
@section('title', '产品介绍')
@section('main_class', 'w-full')

{{-- 文档中心 · 产品介绍（M27 自静态 public/docs/index.html 迁移，统一全站头尾布局） --}}
@section('content')
<div class="bg-gradient-to-b from-brand-50 via-white to-white">
    <div class="mx-auto max-w-6xl px-6 pt-20 pb-16 text-center">
        <span class="inline-flex items-center gap-2 rounded-full bg-brand-100 px-4 py-1.5 text-sm font-semibold text-brand-700">● 开源 MIT · 自托管 · 数据自有</span>
        <h1 class="mt-6 text-4xl leading-tight font-bold tracking-tight text-zinc-900 md:text-5xl">洞察每一次访问<br>驱动每一个增长决策</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-zinc-600">Monit 是一套开源的网站监控与分析系统:实时统计、会话回放、点击热图、转化目标一应俱全;不依赖 Cookie、不影响性能、数据 100% 存储在你自己的服务器。</p>
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="{{ route('register') }}" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700">立即免费体验</a>
            <a href="{{ route('docs.install') }}" class="rounded-xl border border-zinc-300 bg-white px-6 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50">自部署安装 →</a>
        </div>
    </div>
</div>

<div class="border-y border-zinc-200 bg-zinc-50">
    <div class="mx-auto grid max-w-6xl grid-cols-2 gap-4 px-6 py-8 text-center md:grid-cols-4">
        <div><b class="block text-2xl text-brand-600">&lt; 1 KB</b><span class="text-sm text-zinc-500">采集脚本体积</span></div>
        <div><b class="block text-2xl text-brand-600">毫秒级</b><span class="text-sm text-zinc-500">数据刷新延迟</span></div>
        <div><b class="block text-2xl text-brand-600">0 Cookie</b><span class="text-sm text-zinc-500">无需同意弹窗</span></div>
        <div><b class="block text-2xl text-brand-600">100%</b><span class="text-sm text-zinc-500">数据归属自有</span></div>
    </div>
</div>

<section id="features" class="mx-auto max-w-6xl px-6 py-16">
    <h2 class="text-center text-3xl font-bold tracking-tight text-zinc-900">核心功能全景</h2>
    <p class="mx-auto mt-3 max-w-xl text-center text-zinc-600">从流量监控到行为分析,从转化追踪到团队协作——一个平台全部覆盖。</p>
    <div class="mt-10 grid gap-5 md:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">📈</div><h3 class="font-semibold text-zinc-900">实时访问统计</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">浏览量、访客数、会话时长、跳出率等核心指标秒级刷新;来源渠道、国家地区、设备浏览器、热门页面多维下钻。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🎬</div><h3 class="font-semibold text-zinc-900">会话回放</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">逐帧还原访客的鼠标轨迹、滚动与点击行为,快速定位体验断点与流失环节。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🔥</div><h3 class="font-semibold text-zinc-900">点击热图</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">页面级点击密度可视化,支持移动/桌面分离,让改版决策有据可依。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🎯</div><h3 class="font-semibold text-zinc-900">转化目标</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">注册、下单、表单提交等自定义目标与漏斗,量化每一次关键行为的转化效率。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🛡️</div><h3 class="font-semibold text-zinc-900">隐私合规</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">无 Cookie 追踪、IP 匿名化、数据保留策略、GDPR 同意横幅,合规内建而非外挂。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🔌</div><h3 class="font-semibold text-zinc-900">开放 API</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">完整 REST API + 像素 SDK + Webhook 事件推送,轻松对接自有 BI、报表与自动化流程。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">👥</div><h3 class="font-semibold text-zinc-900">团队协作</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">多成员、多角色权限管理,团队共享网站数据与标注洞察。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🎨</div><h3 class="font-semibold text-zinc-900">品牌白标</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">Logo、Favicon、主题色、ICP 备案、页脚代码全部后台可配,零代码完成白标定制。</p></div>
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 transition hover:border-brand-300 hover:shadow-lg hover:shadow-brand-600/5"><div class="mb-4 text-2xl">🌍</div><h3 class="font-semibold text-zinc-900">多语言多货币</h3><p class="mt-2 text-sm leading-relaxed text-zinc-600">简体/繁体中文、英语、俄语、白俄罗斯语、马来语;支付支持 20+ 网关与多货币结算。</p></div>
    </div>
</section>
<section id="why" class="bg-zinc-950 py-16">
    <div class="mx-auto max-w-6xl px-6">
        <h2 class="text-center text-3xl font-bold tracking-tight text-white">为什么选择 Monit</h2>
        <p class="mt-3 text-center text-zinc-400">当数据成为资产,"放哪里"比"存多少"更重要。</p>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6"><b class="block bg-gradient-to-r from-indigo-300 to-indigo-500 bg-clip-text text-xl text-transparent">自主可控</b><h3 class="mt-2 font-semibold text-white">数据 100% 自有</h3><p class="mt-2 text-sm leading-relaxed text-zinc-400">数据存储在你部署的服务器与数据库,随时备份、导出、迁移,不受任何第三方掣肘。</p></div>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6"><b class="block bg-gradient-to-r from-indigo-300 to-indigo-500 bg-clip-text text-xl text-transparent">极致轻量</b><h3 class="mt-2 font-semibold text-white">性能零负担</h3><p class="mt-2 text-sm leading-relaxed text-zinc-400">采集脚本小于 1 KB,sendBeacon 无阻塞上报,目标页性能分毫不受影响。</p></div>
            <div class="rounded-2xl border border-zinc-800 bg-zinc-900 p-6"><b class="block bg-gradient-to-r from-indigo-300 to-indigo-500 bg-clip-text text-xl text-transparent">天生合规</b><h3 class="mt-2 font-semibold text-white">GDPR 友好</h3><p class="mt-2 text-sm leading-relaxed text-zinc-400">无 Cookie 方案 + 保留策略 + 同意管理,满足欧盟隐私法规要求。</p></div>
        </div>
    </div>
</section>

<section id="compare" class="mx-auto max-w-6xl px-6 py-16">
    <h2 class="text-center text-3xl font-bold tracking-tight text-zinc-900">与主流方案对比</h2>
    <div class="mt-10 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-5 py-3 font-semibold">能力</th><th class="px-5 py-3 font-semibold">Monit(自托管)</th><th class="px-5 py-3 font-semibold">SaaS 分析工具</th><th class="px-5 py-3 font-semibold">自建日志脚本</th></tr></thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-600">
                <tr><td class="px-5 py-3">数据归属</td><td class="px-5 py-3 font-medium text-emerald-700">完全自有</td><td class="px-5 py-3">托管在厂商</td><td class="px-5 py-3 font-medium text-emerald-700">自有</td></tr>
                <tr><td class="px-5 py-3">部署成本</td><td class="px-5 py-3 font-medium text-emerald-700">一键 Docker / 5 分钟手动</td><td class="px-5 py-3">零部署</td><td class="px-5 py-3">数周开发</td></tr>
                <tr><td class="px-5 py-3">功能完整度</td><td class="px-5 py-3 font-medium text-emerald-700">统计/回放/热图/目标/团队</td><td class="px-5 py-3 font-medium text-emerald-700">完整</td><td class="px-5 py-3">仅基础统计</td></tr>
                <tr><td class="px-5 py-3">隐私合规</td><td class="px-5 py-3 font-medium text-emerald-700">无 Cookie + GDPR 内建</td><td class="px-5 py-3">视厂商而定</td><td class="px-5 py-3">需自行实现</td></tr>
                <tr><td class="px-5 py-3">二次开发</td><td class="px-5 py-3 font-medium text-emerald-700">MIT 开源 + 完整文档</td><td class="px-5 py-3">封闭</td><td class="px-5 py-3 font-medium text-emerald-700">可改</td></tr>
                <tr><td class="px-5 py-3">长期成本</td><td class="px-5 py-3 font-medium text-emerald-700">服务器成本,按需扩容</td><td class="px-5 py-3">按流量计费递增</td><td class="px-5 py-3">人力维护</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="rounded-3xl bg-gradient-to-r from-brand-600 to-indigo-600 px-8 py-12 text-center">
        <h2 class="text-3xl font-bold text-white">准备好看清你的流量了吗?</h2>
        <p class="mt-3 text-brand-100">注册账户,或在你的服务器上 5 分钟完成部署。</p>
        <a href="{{ route('register') }}" class="mt-6 inline-block rounded-xl bg-white px-6 py-3 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">免费开始</a>
    </div>
    <div class="mt-10 flex flex-wrap justify-center gap-6 text-sm">
        <a href="{{ route('docs.install') }}" class="text-brand-600 hover:underline">安装指南</a>
        <a href="{{ route('docs.usage') }}" class="text-brand-600 hover:underline">使用手册</a>
        <a href="{{ route('api.docs') }}" class="text-brand-600 hover:underline">API 文档</a>
        <a href="{{ route('help') }}" class="text-brand-600 hover:underline">帮助中心</a>
    </div>
</section>
@endsection
