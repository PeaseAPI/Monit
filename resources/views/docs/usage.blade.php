@extends('layouts.public')
@section('title', '使用手册')
@section('main_class', 'mx-auto w-full max-w-4xl px-6 py-12')

{{-- 文档中心 · 使用手册（M27 自静态 public/docs/usage.html 迁移，统一全站头尾布局） --}}
@section('content')
<div class="text-center">
    <h1 class="text-4xl font-bold tracking-tight text-zinc-900">使用手册</h1>
    <p class="mt-3 text-zinc-600">从接入第一个网站,到用数据驱动增长。</p>
</div>
<div class="mt-8 flex flex-wrap justify-center gap-3 text-sm">
    <a href="#quick" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">快速上手</a>
    <a href="#pixel" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">接入像素</a>
    <a href="#stats" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">数据洞察</a>
    <a href="#replay" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">回放与热图</a>
    <a href="#goals" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">转化目标</a>
    <a href="#branding" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">品牌定制</a>
    <a href="#team" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">团队与套餐</a>
</div>

<section id="quick" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">1 · 快速上手（3 步）</h2>
    <p class="mt-2 text-sm text-zinc-600">注册 → 添加网站 → 嵌入一行代码,数据即刻开始流入。</p>
    <ol class="mt-5 space-y-4">
        <li class="rounded-2xl border border-zinc-200 bg-white p-5"><b class="text-zinc-900">① 注册账户</b><p class="mt-1 text-sm text-zinc-600">在首页点击"免费开始"完成注册;免费版即可完整分析 1 个网站。</p></li>
        <li class="rounded-2xl border border-zinc-200 bg-white p-5"><b class="text-zinc-900">② 添加网站</b><p class="mt-1 text-sm text-zinc-600">进入控制台 → 网站 → 添加网站,填写域名并选择采集模式(高级/轻量),保存后获得唯一的像素 Key。</p></li>
        <li class="rounded-2xl border border-zinc-200 bg-white p-5"><b class="text-zinc-900">③ 嵌入像素</b><p class="mt-1 text-sm text-zinc-600">把系统生成的一行 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">&lt;script&gt;</code> 标签复制到目标页面 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">&lt;/body&gt;</code> 前,完成。</p></li>
    </ol>
</section>

<section id="pixel" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">2 · 接入像素 SDK</h2>
    <h3 class="mt-5 font-semibold text-zinc-800">标准接入（一行代码）</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>&lt;script defer src="https://你的分析域名/assets/pixel/monit.js"
        data-monit-key="你的像素Key"&gt;&lt;/script&gt;</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">高级模式 vs 轻量模式</h3>
    <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-zinc-600">
        <li><b>高级模式（advanced）</b>：完整会话/访客/事件体系,支持回放、热图、自定义参数与转化目标。推荐默认。</li>
        <li><b>轻量模式（lightweight）</b>：无 Cookie、无访客标识,仅聚合浏览事件,适合隐私极端敏感或超高频站点。</li>
    </ul>
    <h3 class="mt-5 font-semibold text-zinc-800">上报转化目标</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>monit('goal', { key: 'signup', value: 0 });</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">SPA 单页应用</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>monit('pageview', { url: location.pathname + location.search });</code></pre>
    <div class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">SDK 自动尊重 Do-Not-Track 设置;上报使用 sendBeacon,页面卸载不丢数据。</div>
</section>

<section id="stats" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">3 · 数据洞察</h2>
    <p class="mt-2 text-sm text-zinc-600">统计中心提供 30+ 维度,支持今日/7 天/30 天/自定义区间切换。</p>
    <ul class="mt-4 list-disc space-y-1.5 pl-6 text-sm text-zinc-600">
        <li><b>概览</b>:浏览量、访客数、会话时长、跳出率、增长曲线;</li>
        <li><b>来源</b>:引荐网站、渠道、搜索引擎关键词、UTM 参数;</li>
        <li><b>地理与设备</b>:国家/城市、大洲、时区、语言、浏览器/OS/设备;</li>
        <li><b>行为</b>:落地页、退出页、热门页面、进出路径;</li>
        <li><b>人群</b>:新回访、活跃时段(星期×小时热力格)、自定义参数;</li>
        <li><b>标注</b>:在时间轴上标记活动/发版,对比前后效果。</li>
    </ul>
</section>

<section id="replay" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">4 · 会话回放与热图</h2>
    <h3 class="mt-5 font-semibold text-zinc-800">会话回放</h3>
    <p class="mt-2 text-sm text-zinc-600">访客列表 → 选择会话 → 播放:鼠标轨迹、滚动深度、点击与输入(脱敏)逐帧还原。支持倍速与关键事件跳转。</p>
    <h3 class="mt-5 font-semibold text-zinc-800">点击热图</h3>
    <p class="mt-2 text-sm text-zinc-600">热图页面输入目标 URL,系统渲染点击密度叠加层;移动端与桌面端分开统计。可设置采集时长与样本量。</p>
</section>

<section id="goals" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">5 · 转化目标与漏斗</h2>
    <ol class="mt-4 list-decimal space-y-1.5 pl-6 text-sm text-zinc-600">
        <li>网站 → 目标 → 添加目标,选择类型(访问页面/事件/停留时长);</li>
        <li>前端在关键行为处调用 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">monit('goal', …)</code>;</li>
        <li>目标报表查看触发次数、转化率与带来的浏览量,评估渠道质量。</li>
    </ol>
</section>
<section id="branding" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">6 · 品牌定制（后台零代码）</h2>
    <p class="mt-2 text-sm text-zinc-600">管理员后台 → 系统设置 → 品牌与外观。</p>
    <ul class="mt-4 list-disc space-y-1.5 pl-6 text-sm text-zinc-600">
        <li><b>站点名称 / Logo（亮色/深色）/ Favicon</b>:全站替换品牌元素;</li>
        <li><b>品牌主色</b>:一个色值运行时生成整阶主题色(按钮/链接/图表);</li>
        <li><b>落地页模板</b>:内置 Default 大气模板,二开可上传自定义模板;</li>
        <li><b>页脚 ICP 备案号</b>:自动链接到工信部备案系统;</li>
        <li><b>页脚自定义 HTML/JS</b>:挂载统计代码、客服组件、公安备案图标等;</li>
        <li><b>自定义 HEAD CSS/JS 与 FOOTER JS</b>:全站注入任意代码。</li>
    </ul>
</section>

<section id="team" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">7 · 团队协作与套餐</h2>
    <ul class="mt-4 list-disc space-y-1.5 pl-6 text-sm text-zinc-600">
        <li><b>团队成员</b>:邀请成员并授予只读/编辑权限,共享网站数据;</li>
        <li><b>套餐与配额</b>:免费/专业/自定义套餐,限制网站数、事件量、回放与热图配额,超额自动提醒;</li>
        <li><b>账单与发票</b>:支持 20+ 支付网关(支付宝/微信/Stripe/PayPal…),多货币定价与税率配置;</li>
        <li><b>域名监控与 SEO 工具</b>:域名 WHOIS/到期监控、SEO 审计、关键词排名跟踪(需配置 SerpApi)、外链概览;</li>
        <li><b>联盟计划</b>:可开启推荐返佣,追踪注册转化与提现;</li>
        <li><b>API</b>:账户 → API 密钥,REST 全量数据读写,详见 <a href="{{ route('api.docs') }}" class="text-brand-600 hover:underline">API 文档</a>。</li>
    </ul>
    <div class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">需要深度定制?阅读仓库内 <code class="rounded bg-white/60 px-1.5 py-0.5 text-xs">docs/二次开发指南.md</code>,含完整代码逻辑讲解与扩展点说明。</div>
</section>

<div class="mt-16 border-t border-zinc-200 pt-8">
    <div class="flex flex-wrap justify-center gap-6 text-sm">
        <a href="{{ route('docs.index') }}" class="text-brand-600 hover:underline">产品介绍</a>
        <a href="{{ route('docs.install') }}" class="text-brand-600 hover:underline">安装指南</a>
        <a href="{{ route('api.docs') }}" class="text-brand-600 hover:underline">API 文档</a>
        <a href="{{ route('help') }}" class="text-brand-600 hover:underline">帮助中心</a>
    </div>
</div>
@endsection
