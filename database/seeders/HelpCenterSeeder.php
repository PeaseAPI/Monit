<?php

namespace Database\Seeders;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 帮助中心官方文档种子：10 大分类 / 45 篇「设置文档 + 使用文档」（仿 help.aliyun.com 文档体系）。
 *
 * - 幂等：按 url updateOrCreate，可反复执行（内容以本文件为准，会覆盖后台同 slug 的改动）；
 * - content 为 HTML 片段（详情页 {!! !!} 输出）：h2/h3 生成「本页目录」锚点；
 *   提示条 .doc-note--info/warn/tip；FAQ 用 <details class="doc-faq">；步骤列表 ol.doc-steps；
 * - 执行：php artisan db:seed --class=HelpCenterSeeder --force
 */
class HelpCenterSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = User::where('type', 1)->orderBy('user_id')->value('user_id')
            ?? (User::query()->value('user_id') ?: 1);

        foreach ($this->catalog() as $catIndex => $cat) {
            $category = HelpCategory::updateOrCreate(
                ['url' => $cat['url']],
                [
                    'user_id' => $adminId,
                    'title' => $cat['title'],
                    'icon' => $cat['icon'],
                    'order' => $catIndex,
                    'datetime' => now(),
                ]
            );

            foreach ($cat['articles'] as $i => $article) {
                HelpArticle::updateOrCreate(
                    ['url' => $article['url']],
                    [
                        'user_id' => $adminId,
                        'category_id' => $category->category_id,
                        'title' => $article['title'],
                        'content' => $article['content'],
                        'description' => $article['desc'],
                        'is_published' => true,
                        'order' => $i,
                        'datetime' => now(),
                    ]
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function catalog(): array
    {
        return [
            // ============================================================
            // 一、快速入门
            // ============================================================
            [
                'url' => 'getting-started', 'title' => '快速入门', 'icon' => 'rocket',
                'articles' => [
                    [
                        'url' => 'product-overview',
                        'title' => 'Monit 是什么：产品概览与核心能力',
                        'desc' => '了解 Monit 自托管网站统计系统的定位、核心功能与整体架构。',
                        'content' => <<<'HTML'
<p>Monit 是一套<strong>自托管（Self-hosted）网站统计与分析系统</strong>：部署在自己的服务器上，数据 100% 归自己所有；不使用 Cookie 追踪访客，天然符合 GDPR 等隐私法规。</p>
<h2>核心能力一览</h2>
<ul>
<li><strong>网站流量统计</strong>：实时在线人数、访客、浏览量、会话时长、跳出率，以及来源、地理、设备、行为、UTM 等多维报表。</li>
<li><strong>会话回放</strong>：基于 rrweb 的操作回放，逐帧还原访客在页面上的真实操作（支持隐私屏蔽）。</li>
<li><strong>热力图</strong>：点击热图与滚动深度图，直观呈现页面上访客最关注的位置。</li>
<li><strong>事件与转化目标</strong>：自定义事件上报 + 转化目标转化率统计。</li>
<li><strong>SEO 工具</strong>：关键词排名追踪、外链监控、全站 SEO 审计（AI 优化建议）、批量 SEO 小工具。</li>
<li><strong>套餐与计费</strong>：多套餐、22+ 支付渠道、发票、兑换码、推荐（联盟）计划。</li>
</ul>
<h2>整体架构</h2>
<table>
<thead><tr><th>组成</th><th>说明</th></tr></thead>
<tbody>
<tr><td>追踪脚本 monit.js</td><td>部署在被统计网站前端，采集页面浏览与行为数据</td></tr>
<tr><td>Monit 服务端</td><td>接收、聚合、存储数据并渲染统计报表</td></tr>
<tr><td>用户后台</td><td>网站管理、统计查看、SEO 工具、账户与套餐</td></tr>
<tr><td>管理后台</td><td>系统设置、用户/套餐/支付/内容管理</td></tr>
</tbody>
</table>
<div class="doc-note doc-note--tip"><p><strong>提示：</strong>想快速跑起来，请直接阅读下一篇《五分钟接入：从注册到看到数据》。</p></div>
HTML,
                    ],
                    [
                        'url' => 'quick-start',
                        'title' => '五分钟接入：从注册到看到数据',
                        'desc' => '注册账户、创建网站、安装追踪代码、验证数据上报的完整流程。',
                        'content' => <<<'HTML'
<p>本教程带你完成 Monit 接入的完整流程，约五分钟即可看到第一批数据。</p>
<h2>第 1 步：注册并登录</h2>
<ol class="doc-steps">
<li>打开站点首页，点击右上角<strong>注册</strong>，使用邮箱完成注册并激活账户。</li>
<li>登录后进入<strong>仪表盘</strong>。</li>
</ol>
<h2>第 2 步：创建网站</h2>
<ol class="doc-steps">
<li>进入<strong>网站管理</strong>，点击<strong>添加网站</strong>。</li>
<li>填写网站名称与域名（如 <code>example.com</code>），采集模式保持默认的<strong>完整模式</strong>。</li>
<li>保存后系统会为该网站生成唯一的<strong>像素密钥（Pixel Key）</strong>。</li>
</ol>
<h2>第 3 步：安装追踪代码</h2>
<p>在「网站管理 → 安装代码」中复制生成的脚本，粘贴到网站每个页面 <code>&lt;head&gt;</code> 标签内：</p>
<pre><code>&lt;script src="https://你的Monit域名/assets/pixel/monit.js" data-website-id="你的像素密钥" data-mode="advanced" data-replay="1" async&gt;&lt;/script&gt;</code></pre>
<div class="doc-note doc-note--warn"><p><strong>注意：</strong>SPA 应用（React / Vue / Next.js 等）在完整模式下会自动追踪路由切换，无需额外代码；如需在用户同意 Cookie 后再采集，请给脚本加 <code>data-manual</code> 属性，并在同意回调里调用 <code>window.MonitPixel.init()</code>。</p></div>
<h2>第 4 步：验证数据</h2>
<ol class="doc-steps">
<li>用浏览器访问你的网站任意页面。</li>
<li>回到 Monit「实时」页面，应能在几秒内看到自己的在线访问。</li>
<li>进入网站统计页，确认「访客 / 浏览量」开始累计。</li>
</ol>
<div class="doc-note doc-note--info"><p><strong>看不到数据？</strong>请确认：脚本位于 <code>&lt;head&gt;</code> 且未被广告拦截插件屏蔽；像素密钥与网站一致；浏览器未开启严格的追踪防护。</p></div>
HTML,
                    ],
                    [
                        'url' => 'install-tracking-code',
                        'title' => '安装追踪代码详解（含 SPA / 采集模式 / 排除自己）',
                        'desc' => 'monit.js 的全部安装参数、两种采集模式、常见框架接入与常见安装问题。',
                        'content' => <<<'HTML'
<p>追踪脚本是 Monit 采集数据的入口，本文详细说明脚本参数、采集模式与各框架接入方式。</p>
<h2>脚本完整示例与参数</h2>
<pre><code>&lt;script src="https://monit.example.com/assets/pixel/monit.js"
  data-website-id="像素密钥"
  data-mode="advanced"
  data-replay="1"
  data-manual
  async&gt;&lt;/script&gt;</code></pre>
<table>
<thead><tr><th>属性</th><th>必填</th><th>说明</th></tr></thead>
<tbody>
<tr><td>src</td><td>是</td><td>指向你的 Monit 实例的 <code>/assets/pixel/monit.js</code>（同源部署，无跨域问题）</td></tr>
<tr><td>data-website-id</td><td>是</td><td>网站像素密钥，可在「网站管理 → 安装代码」中复制</td></tr>
<tr><td>data-mode</td><td>否</td><td><code>advanced</code>（完整模式，默认）：页面停留、滚动、外链点击、回放等完整行为；<code>lightweight</code>（轻量模式）：仅页面浏览，开销最小</td></tr>
<tr><td>data-replay</td><td>否</td><td><code>1</code> 开启会话回放采集（自动加载 rrweb，无需站点另行引入）；轻量模式不支持</td></tr>
<tr><td>data-manual</td><td>否</td><td>禁止自动启动，改由代码在合适时机调用 <code>window.MonitPixel.init()</code>（如 Cookie 同意后）</td></tr>
</tbody>
</table>
<h2>SPA 应用接入</h2>
<p>完整模式下脚本会自动监听 <code>history.pushState / replaceState / popstate / hashchange</code>，路由切换自动记录页面浏览，<strong>无需手动埋点</strong>：</p>
<pre><code>&lt;!-- React / Vue / Next.js 等直接引入即可 --&gt;
&lt;script src="https://monit.example.com/assets/pixel/monit.js" data-website-id="像素密钥" data-mode="advanced" data-replay="1" async&gt;&lt;/script&gt;</code></pre>
<h2>Cookie 同意后才开始统计</h2>
<pre><code>&lt;script src="https://monit.example.com/assets/pixel/monit.js" data-website-id="像素密钥" data-mode="advanced" data-manual async&gt;&lt;/script&gt;
&lt;script&gt;
  document.getElementById('consent-btn').addEventListener('click', function () {
    window.MonitPixel.init();
  });
&lt;/script&gt;</code></pre>
<h2>排除自己的访问</h2>
<ul>
<li>方案 A：使用浏览器的广告拦截扩展屏蔽 <code>monit.js</code>，或用无痕窗口访问。</li>
<li>方案 B：管理员在系统设置中配置 IP 排除，把办公网出口 IP 排除出统计。</li>
</ul>
<h2>常见安装问题</h2>
<details class="doc-faq"><summary>脚本报 404</summary><div>确认 <code>src</code> 域名与 Monit 部署域名一致，且 Web 服务器未拦截 <code>/assets/pixel/monit.js</code>。</div></details>
<details class="doc-faq"><summary>只有浏览量，没有行为数据</summary><div>多半使用了 <code>lightweight</code> 轻量模式，改为 <code>advanced</code> 完整模式即可。</div></details>
<details class="doc-faq"><summary>代码放在 body 末尾可以吗</summary><div>可以，但推荐放 <code>&lt;head&gt;</code> 并保持 <code>async</code>，避免快速跳转丢失页面浏览。</div></details>
HTML,
                    ],
                    [
                        'url' => 'dashboard-metrics-guide',
                        'title' => '仪表盘导览与核心指标解读',
                        'desc' => '仪表盘各卡片、统计页面结构与访客/浏览量/跳出率/会话时长等指标定义。',
                        'content' => <<<'HTML'
<p>登录后进入<strong>仪表盘</strong>：它汇总了你所有网站的实时状态与关键趋势。</p>
<h2>仪表盘结构</h2>
<ul>
<li><strong>网站概览</strong>：每个网站的访客/浏览量趋势图。</li>
<li><strong>实时人数</strong>：当前在线访客数，点击进入实时页面查看来源明细。</li>
<li><strong>快捷入口</strong>：安装代码、目标、回放、热图等常用功能。</li>
</ul>
<h2>统计页面布局</h2>
<p>进入任一网站统计页后，自上而下依次是：</p>
<ol>
<li><strong>顶部趋势图</strong>：访客 / 浏览量 / 会话时长 / 跳出率曲线与时间范围选择器。</li>
<li><strong>维度卡片</strong>：页面、来源、地理（国家/城市）、设备（浏览器/系统/分辨率）、语言、时区等。</li>
<li><strong>行为区</strong>：行为流、外链点击、事件、UTM 下钻。</li>
</ol>
<h2>指标定义</h2>
<table>
<thead><tr><th>指标</th><th>定义</th></tr></thead>
<tbody>
<tr><td>访客（Visitors）</td><td>按访客唯一标识去重的人数，当天重复访问只计 1 次</td></tr>
<tr><td>浏览量（Pageviews）</td><td>页面浏览事件总数，同一访客刷新也累计</td></tr>
<tr><td>会话时长</td><td>同一会话内首次与最后一次页面浏览的时间间隔</td></tr>
<tr><td>跳出率</td><td>单页会话占比：进入后未再浏览其他页面即离开</td></tr>
<tr><td>实时在线</td><td>最近数分钟内活跃的访客数</td></tr>
</tbody>
</table>
<div class="doc-note doc-note--tip"><p><strong>提示：</strong>所有报表均支持时间范围切换与维度点击下钻，详见《数据过滤与维度下钻》。</p></div>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 二、网站与统计
            // ============================================================
            [
                'url' => 'websites-analytics', 'title' => '网站与统计', 'icon' => 'chart',
                'articles' => [
                    [
                        'url' => 'manage-websites',
                        'title' => '网站管理：添加 / 导入 / 模式切换 / 删除',
                        'desc' => '网站的添加与批量导入、像素密钥、采集模式切换、域名绑定与数据保留。',
                        'content' => <<<'HTML'
<p>「网站管理」是使用 Monit 的起点，每个网站拥有独立的像素密钥与统计数据。</p>
<h2>添加与批量导入</h2>
<ul>
<li><strong>单个添加</strong>：网站管理 → 添加网站，填写名称与域名，选择采集模式。</li>
<li><strong>批量导入</strong>：支持按表格批量导入多个网站，适合一次迁移大量站点。</li>
</ul>
<h2>像素密钥（Pixel Key）</h2>
<p>每个网站的唯一标识，追踪脚本通过 <code>data-website-id</code> 上报。如怀疑泄露，可在编辑页重新生成（旧脚本随即失效，需要同步替换）。</p>
<h2>采集模式切换</h2>
<table>
<thead><tr><th>模式</th><th>采集内容</th><th>适用场景</th></tr></thead>
<tbody>
<tr><td>完整模式（advanced）</td><td>页面浏览 + 停留/滚动/外链点击等行为 + 会话回放</td><td>绝大多数网站</td></tr>
<tr><td>轻量模式（lightweight）</td><td>仅页面浏览</td><td>极简、性能敏感页面；不支持回放与热图</td></tr>
</tbody>
</table>
<div class="doc-note doc-note--warn"><p><strong>切换模式后</strong>需要同步更新网站上的 <code>data-mode</code> 属性，否则以前端脚本实际值为准。</p></div>
<h2>数据保留与删除</h2>
<ul>
<li>网站统计数据按套餐的数据保留期滚动清理，超期数据不可恢复。</li>
<li>删除网站会<strong>立即删除其全部统计数据</strong>且不可恢复，操作前请谨慎确认。</li>
</ul>
HTML,
                    ],
                    [
                        'url' => 'reports-guide',
                        'title' => '统计报告详解：来源 / 地理 / 设备 / 行为 / UTM',
                        'desc' => '统计页各维度报表的含义与用法：页面、来源、国家城市、浏览器设备、行为流、外链与下载。',
                        'content' => <<<'HTML'
<p>进入网站统计页后，可以从多个维度理解访客行为。所有维度卡片都支持点击进入明细列表。</p>
<h2>来源（Referrers）</h2>
<ul>
<li><strong>来源站点</strong>：访客从哪些网站跳转而来，点击某来源可继续下钻其具体路径。</li>
<li><strong>来源分类</strong>：搜索引擎、社交网络、直接访问等归类汇总。</li>
<li><strong>UTM 下钻</strong>：按 <code>utm_source / utm_medium / utm_campaign</code> 等参数拆解活动流量。</li>
</ul>
<h2>地理（Geo）</h2>
<p>国家与城市维度基于 GeoIP 数据库解析 IP 而来。管理员需要配置 mmdb 数据库（见管理员指南《GeoIP 数据库配置》），否则地理维度为空。</p>
<h2>设备与环境</h2>
<ul>
<li><strong>浏览器 / 操作系统</strong>：按 User-Agent 解析。</li>
<li><strong>设备类型</strong>：桌面 / 手机 / 平板。</li>
<li><strong>分辨率 / 语言 / 时区</strong>：由访客浏览器上报。</li>
</ul>
<h2>行为</h2>
<ul>
<li><strong>行为流（Behavior）</strong>：访客在站内的浏览路径串联，帮助理解典型浏览动线。</li>
<li><strong>外链点击</strong>：点击站外链接的去向统计与具体路径。</li>
<li><strong>事件</strong>：自定义事件上报与转化目标达成（见《自定义事件与转化目标》）。</li>
</ul>
<h2>实时</h2>
<p>实时页面展示当前在线访客及其来源页面、地理位置与正在浏览的内容，适合验证埋点与活动投放效果。</p>
HTML,
                    ],
                    [
                        'url' => 'custom-events-goals',
                        'title' => '自定义事件与转化目标',
                        'desc' => '用 window.monitGoal 上报转化，创建目标统计转化率，事件与目标的区别。',
                        'content' => <<<'HTML'
<p>事件与目标是衡量业务动作（注册、下单、下载、表单提交）的核心手段。</p>
<h2>事件与目标的区别</h2>
<ul>
<li><strong>事件</strong>：前端任意上报的行为记录，用于「看」——不需要预先创建。</li>
<li><strong>目标（Goal）</strong>：在后台预先定义的关键行为，用于「考核转化」——统计转化率与达成次数。</li>
</ul>
<h2>第 1 步：在关键动作处上报</h2>
<p>在需要统计的动作完成时调用全局函数 <code>window.monitGoal(key)</code>：</p>
<pre><code>&lt;!-- 例：注册表单提交成功后记录 signup 目标 --&gt;
&lt;script&gt;
  document.getElementById('signup-form').addEventListener('submit', function () {
    window.monitGoal('signup');
  });
&lt;/script&gt;</code></pre>
<div class="doc-note doc-note--info"><p><strong>异步场景</strong>（如支付回调、AJAX 登录）同样适用：在请求成功的回调里调用即可。</p></div>
<h2>第 2 步：创建目标</h2>
<ol class="doc-steps">
<li>进入网站统计页 → <strong>目标</strong> → 新建目标。</li>
<li>填写目标名称与<strong>目标 key</strong>（与 <code>monitGoal()</code> 传入的 key 一致，如 <code>signup</code>）。</li>
<li>保存后，之后触发的该行为会被计入转化。</li>
</ol>
<h2>查看转化数据</h2>
<p>目标页展示每个目标的达成次数与<strong>转化率</strong>（达成次数 / 访客数），可切换时间范围对比趋势。</p>
<h2>常见问题</h2>
<details class="doc-faq"><summary>目标一直没有数据</summary><div>检查两点：① 前端调用的 key 与目标 key 是否完全一致；② 调用时机是否发生在脚本加载之后（脚本 async 加载，尽量放在页面生命周期事件而非 <code>&lt;head&gt;</code> 同步执行）。</div></details>
<details class="doc-faq"><summary>能否给事件附加数值（如订单金额）</summary><div>当前版本事件仅记录触发次数；金额类指标可由支付记录与套餐报表补充。</div></details>
HTML,
                    ],
                    [
                        'url' => 'visitors-sessions',
                        'title' => '访客与会话：访客列表、访客详情与会话回放入口',
                        'desc' => '访客唯一标识机制、访客列表与详情页、从访客跳转会话回放。',
                        'content' => <<<'HTML'
<p>「访客」页将零散的页面浏览聚合成一个个访客档案，是排查异常流量、分析高价值访客的工具。</p>
<h2>访客识别机制</h2>
<p>Monit 不使用 Cookie：访客标识由服务端基于请求特征（IP 哈希 + User-Agent 等组合）计算得出，可跨会话识别同一访客，同时保护隐私。</p>
<h2>访客列表</h2>
<ul>
<li>默认按最近活跃排序，展示每个访客的浏览量、会话数、最近访问时间与来源。</li>
<li>点击任一访客进入<strong>访客详情</strong>。</li>
</ul>
<h2>访客详情</h2>
<ul>
<li><strong>基本信息</strong>：设备、浏览器、操作系统、分辨率、语言、地理位置。</li>
<li><strong>会话列表</strong>：该访客的每次到访（会话），展开可看每次会话的页面浏览序列。</li>
<li><strong>回放入口</strong>：完整模式下采集的会话，可从会话记录直接进入<strong>会话回放</strong>观看操作过程。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>提示：</strong>配合会话回放食用更佳——先在访客列表发现行为异常（极短停留、异常刷新）的访客，再进入回放确认。</p></div>
HTML,
                    ],
                    [
                        'url' => 'annotations',
                        'title' => '注释：在趋势图上标记重要事件',
                        'desc' => '用注释记录改版、活动上线等节点，在趋势图与统计页对照流量变化。',
                        'content' => <<<'HTML'
<p>注释（Annotations）允许你在时间轴上标记「发生某事的时刻」，例如：网站改版上线、营销活动开始、出现故障等，方便对照流量曲线理解变化原因。</p>
<h2>创建注释</h2>
<ol class="doc-steps">
<li>进入网站统计页 → <strong>注释</strong>。</li>
<li>选择日期，填写注释内容（如「首页改版上线」）。</li>
<li>保存后，该日期会在趋势图与统计页顶部以标记形式展示，悬停/点击可查看内容。</li>
</ol>
<h2>管理注释</h2>
<ul>
<li>注释列表支持编辑与删除。</li>
<li>注释按网站隔离：只对本网站的趋势图生效。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>用法建议：</strong>每次发版或投放前后各加一条注释，回头复盘流量波动时能省大量翻日志的时间。</p></div>
HTML,
                    ],
                    [
                        'url' => 'public-statistics-sharing',
                        'title' => '公开分享统计（可选密码保护）',
                        'desc' => '把网站统计页公开分享给团队或客户，可加访问密码，随时撤销。',
                        'content' => <<<'HTML'
<p>每个网站都可以生成一个<strong>公开统计链接</strong>，让未登录 Monit 的人（同事、客户）直接查看该站的统计数据，无需分享账户。</p>
<h2>开启公开分享</h2>
<ol class="doc-steps">
<li>进入网站编辑页（或统计页设置区）→ 找到<strong>公开统计</strong>开关。</li>
<li>开启后得到形如 <code>/statistics/{pixel_key}</code> 的公开链接。</li>
<li>可选设置<strong>访问密码</strong>：访客首次打开需输入密码，之后在浏览器内记住。</li>
</ol>
<h2>公开页内容</h2>
<p>公开页与内部统计页结构一致（趋势图、来源、地理、设备等），但<strong>不包含</strong>访客列表、会话回放等敏感明细，保证可分享性。</p>
<h2>安全与撤销</h2>
<ul>
<li>公开链接基于像素密钥：若链接外泄，可重新生成像素密钥使旧链接失效（注意同步更新安装代码）。</li>
<li>随时可关闭公开开关，立即下线公开页。</li>
</ul>
<div class="doc-note doc-note--warn"><p><strong>注意：</strong>对外分享前请确认统计页不含不想公开的数据维度；开启密码可进一步限制范围。</p></div>
HTML,
                    ],
                    [
                        'url' => 'filters-drilldown',
                        'title' => '数据过滤与维度下钻',
                        'desc' => '时间范围切换、筛选条件组合、维度点击下钻，精确定位目标流量。',
                        'content' => <<<'HTML'
<p>统计页的上层能力是「过滤 + 下钻」，两者组合可以回答诸如「过去 7 天来自 Google 移动端的访客都看了哪些页面」这类问题。</p>
<h2>时间范围</h2>
<p>统计页右上角可切换：今日 / 昨日 / 近 7 天 / 近 30 天 / 今年 / 全部 / 自定义区间。切换后所有卡片与图表同步刷新。</p>
<h2>过滤条件</h2>
<ul>
<li>点击任一维度条目（如某来源、某国家、某浏览器）可将其加入过滤器。</li>
<li>多个过滤条件之间为<strong>且</strong>关系，页面上会显示当前生效的过滤标签，可单独移除。</li>
<li>过滤对访客列表、行为流等明细页同样生效。</li>
</ul>
<h2>维度下钻</h2>
<ul>
<li><strong>来源 → 来源路径</strong>：点开某来源站点查看具体来源 URL。</li>
<li><strong>国家 → 城市</strong>：地理数据逐层下钻。</li>
<li><strong>UTM</strong>：按 source / medium / campaign 层层拆解活动表现。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>提示：</strong>把「时间范围 + 过滤器」的组合当作临时看板使用，配合注释复盘投放效果非常高效。</p></div>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 三、会话回放
            // ============================================================
            [
                'url' => 'session-replays', 'title' => '会话回放', 'icon' => 'video',
                'articles' => [
                    [
                        'url' => 'replay-intro',
                        'title' => '会话回放简介与工作原理',
                        'desc' => '回放能做什么、rrweb 采集原理、配额与套餐的关系、隐私保护机制。',
                        'content' => <<<'HTML'
<p>会话回放把访客在页面上的真实操作（点击、滚动、输入、路由切换）录制为可逐帧播放的视频式记录，是定位可用性问题、验证转化障碍最直观的工具。</p>
<h2>工作原理</h2>
<ul>
<li>追踪脚本以 <code>data-replay="1"</code> 安装时，会自动加载 <code>rrweb</code> 库，把 DOM 变更序列化为事件流。</li>
<li>事件流随会话上报到 Monit 服务端保存；观看时由播放器还原渲染。</li>
<li>仅<strong>完整模式（advanced）</strong>支持；轻量模式不采集回放。</li>
</ul>
<h2>配额与开关</h2>
<p>回放录制量由三层控制，全部满足才会录制：</p>
<table>
<thead><tr><th>层级</th><th>控制项</th></tr></thead>
<tbody>
<tr><td>套餐</td><td>回放配额（条数）：<strong>0 = 关闭，-1 = 不限量</strong>；未设置视为不限量</td></tr>
<tr><td>站点</td><td>网站级「会话回放」开关</td></tr>
<tr><td>系统</td><td>管理员的全局回放开关与全局配额</td></tr>
</tbody>
</table>
<div class="doc-note doc-note--info"><p><strong>达到配额后</strong>新会话不再录制，历史回放保留可看；回放列表顶部会显示剩余额度横幅。</p></div>
<h2>隐私保护</h2>
<ul>
<li>密码、信用卡等敏感输入默认屏蔽，不进入录制内容。</li>
<li>Monit 不使用 Cookie，回放也不会在访客浏览器留下跨站标识。</li>
<li>如需更严格的合规，可在安装侧将回放属性移除，仅保留统计能力。</li>
</ul>
HTML,
                    ],
                    [
                        'url' => 'replay-enable-config',
                        'title' => '开启与配置会话回放',
                        'desc' => '安装侧参数、网站级开关、套餐配额设置的三步开启流程。',
                        'content' => <<<'HTML'
<p>回放默认在满足条件时自动开启录制。若没有录到，按以下三层逐项检查。</p>
<h2>第 1 步：安装侧</h2>
<p>确认被统计网站的脚本带 <code>data-replay="1"</code> 且 <code>data-mode="advanced"</code>：</p>
<pre><code>&lt;script src="https://你的Monit域名/assets/pixel/monit.js" data-website-id="像素密钥" data-mode="advanced" data-replay="1" async&gt;&lt;/script&gt;</code></pre>
<div class="doc-note doc-note--warn"><p><strong>轻量模式不支持回放</strong>：带 <code>data-mode="lightweight"</code> 或缺省 <code>data-replay</code> 的旧脚本都不会录制。</p></div>
<h2>第 2 步：网站级开关</h2>
<ol class="doc-steps">
<li>登录 Monit → 打开对应网站 → <strong>会话回放</strong>页面。</li>
<li>若站点开关处于关闭状态（页面会有提示条），打开<strong>会话回放开关</strong>。</li>
<li>若显示配额横幅（回放功能已关闭 / 额度为 0），见第 3 步。</li>
</ol>
<h2>第 3 步：套餐与管理员配额</h2>
<ul>
<li><strong>用户侧</strong>：当前套餐不支持回放（配额为 0）时，需要升级套餐。</li>
<li><strong>管理员侧</strong>：管理后台 → 系统设置 → 统计（analytics）中检查<strong>会话回放全局开关</strong>与<strong>回放配额</strong>：配额填 <code>-1</code> 表示不限量，<code>0</code> 表示全局关闭，正数为全局每站录制上限。</li>
</ul>
<h2>验证是否开始录制</h2>
<ol class="doc-steps">
<li>自己访问被统计网站并产生几次交互。</li>
<li>回到 Monit「会话回放」列表刷新，稍候片刻应出现你的会话。</li>
</ol>
HTML,
                    ],
                    [
                        'url' => 'watch-replays',
                        'title' => '观看回放：播放器控制与活动事件',
                        'desc' => '回放列表筛选、播放器播放/倍速/跳转控制、活动事件跳转与访客关联。',
                        'content' => <<<'HTML'
<h2>回放列表</h2>
<p>网站的「会话回放」页按时间倒序展示已录制的会话：每条包含访客标识、浏览器/设备、来源页面、时长与录制时间。可按条件筛选定位目标会话。</p>
<h2>播放器</h2>
<ul>
<li><strong>播放/暂停、进度条、倍速</strong>：使用播放器自带控制条操作，可快速拖动到任意时刻。</li>
<li><strong>画面还原</strong>：播放器按录制时的分辨率还原页面，与访客所见一致。</li>
<li><strong>活动事件</strong>：播放器旁列出该会话的关键活动（页面浏览、点击等），点击事件可跳转到对应画面时刻。</li>
</ul>
<h2>与访客关联</h2>
<p>回放详情内可跳转到该访客的<strong>访客详情</strong>，查看其全部历史会话，把单次回放放进访客的完整行为轨迹里理解。</p>
<h2>删除回放</h2>
<p>回放条目支持删除，删除后录制内容不可恢复（不影响统计报表数据）。</p>
<div class="doc-note doc-note--tip"><p><strong>用法建议：</strong>改版上线后按「来源页 = 改版页面」筛选回放，观察真实访客是否在新版式上完成了预期操作。</p></div>
HTML,
                    ],
                    [
                        'url' => 'replay-faq',
                        'title' => '回放常见问题（没有录制 / 画面异常）',
                        'desc' => '回放列表为空、部分会话缺失、播放异常等问题的系统化排查清单。',
                        'content' => <<<'HTML'
<h2>回放列表一直是空的</h2>
<p>按顺序排查：</p>
<ol class="doc-steps">
<li><strong>脚本版本</strong>：确认站点脚本带 <code>data-replay="1"</code> 与 <code>data-mode="advanced"</code>（轻量模式不支持）。</li>
<li><strong>网站开关</strong>：该网站的「会话回放」开关是否已打开（回放页面顶部有提示）。</li>
<li><strong>套餐配额</strong>：当前套餐回放配额是否为 0（0 = 关闭）；列表顶部的黄色横幅会说明额度限制。</li>
<li><strong>管理员全局开关</strong>：管理后台 → 统计设置中的全局回放开关与全局配额是否开启。</li>
<li><strong>等待录制</strong>：开启后需要新的访客会话才会产生录制，历史会话不会补录。</li>
</ol>
<details class="doc-faq"><summary>自己访问了网站但没有生成回放</summary><div>确认自己不是被排除的 IP；确认访问发生在开关开启之后；回放按会话录制，单页即走的短会话也可能被策略性跳过。</div></details>
<details class="doc-faq"><summary>部分会话有、部分会话没有</summary><div>录制量受套餐/全局配额限制，达到上限后新会话不再录制；也可能是部分访客使用了拦截脚本的浏览器扩展。</div></details>
<details class="doc-faq"><summary>旧版本部署的实例完全不录制</summary><div>早期版本存在回放采集信息被吞的缺陷（服务端读取设置异常导致回放信息未下发）。请升级到最新版本并确认脚本带 <code>data-replay="1"</code>。</div></details>
<details class="doc-faq"><summary>画面排版与真实页面不一致</summary><div>rrweb 按 DOM 快照还原，页面使用了强 CORS 限制的资源或极特殊字体时可能存在渲染差异，不影响行为分析。</div></details>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 四、热力图
            // ============================================================
            [
                'url' => 'heatmaps', 'title' => '热力图', 'icon' => 'fire',
                'articles' => [
                    [
                        'url' => 'heatmap-intro',
                        'title' => '热力图简介：点击热图与滚动深度',
                        'desc' => '热力图类型、底图快照的生成机制、设备维度分开统计的原理。',
                        'content' => <<<'HTML'
<p>热力图回答「访客在看哪里、点哪里」。Monit 提供两类热图：</p>
<ul>
<li><strong>点击热图</strong>：把页面上的点击位置聚合为热度色块，越红越密集。</li>
<li><strong>滚动深度热图</strong>：展示访客在各滚动位置停留/到达的比例，反映内容被看到多深。</li>
</ul>
<h2>底图从哪来</h2>
<div class="doc-note doc-note--info"><p><strong>热力图的底图是访客访问时自动生成的页面快照（rrweb 快照）</strong>，Monit 不需要、也不会在服务端对目标网站截图。因此：新创建的热图在<em>至少有一位访客访问过该页面</em>之前没有底图，属于正常现象。</p></div>
<h2>按设备维度分开统计</h2>
<p>桌面 / 平板 / 手机分别采集与渲染（页面版式随设备不同），查看时需切换设备标签页。</p>
<h2>数据与隐私</h2>
<ul>
<li>热图基于坐标与快照数据聚合，不记录输入内容等敏感信息。</li>
<li>删除热图不会影响其他统计数据。</li>
</ul>
HTML,
                    ],
                    [
                        'url' => 'create-view-heatmaps',
                        'title' => '创建与查看热力图（路径匹配规则）',
                        'desc' => '创建热图、路径匹配规则（纯路径优先匹配含参数地址）、查看与解读。',
                        'content' => <<<'HTML'
<h2>创建热图</h2>
<ol class="doc-steps">
<li>进入网站 → <strong>热力图</strong> → 新建热图。</li>
<li>填写名称与<strong>页面路径</strong>（如 <code>/pricing</code>，可含查询参数如 <code>?utm_source=ad</code>）。</li>
<li>保存。之后访问匹配页面的访客会自动贡献点击 / 滚动数据与底图快照。</li>
</ol>
<h2>路径匹配规则</h2>
<p>热图按「归一化路径」匹配访客上报的页面：</p>
<ul>
<li>创建时填写的路径若<strong>不带查询参数</strong>（如 <code>/pricing</code>），会匹配到该路径下的所有地址，包括带参数的 <code>/pricing?utm_source=ad</code>。</li>
<li>创建时填写的路径若<strong>带查询参数</strong>（如 <code>?utm_source=ad</code>），则精确匹配对应参数组合的地址。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>建议：</strong>多数场景填写纯路径即可覆盖更全；只分析特定活动落地页时再带上查询参数。</p></div>
<h2>查看与解读</h2>
<ul>
<li>详情页顶部切换<strong>点击 / 滚动</strong>两类数据。</li>
<li>切换<strong>设备</strong>（桌面 / 平板 / 手机）分别查看不同版式下的热图。</li>
<li>右侧展示热图对应的分组统计（点击分组数等），帮助量化热点区域。</li>
</ul>
<h2>删除与重建</h2>
<p>热图支持删除。路径写错时建议直接新建正确路径的热图（旧的删除即可），历史数据不会迁移。</p>
HTML,
                    ],
                    [
                        'url' => 'heatmap-faq',
                        'title' => '热力图常见问题（没有底图 / 数据为空）',
                        'desc' => '热图没有底图快照、点击数据为空、多设备无数据等问题的原因与处理。',
                        'content' => <<<'HTML'
<h2>热图打开后没有底图</h2>
<div class="doc-note doc-note--info"><p>底图 = 访客访问该页面时自动生成的 rrweb 快照，<strong>不是</strong>服务端截图。没有底图通常说明该页面还没有（或还没有匹配到）访客快照。</p></div>
<p>处理：</p>
<ol class="doc-steps">
<li>自己用浏览器正常访问一次目标页面（等待页面完全加载）。</li>
<li>回到热图详情刷新，底图应出现。</li>
<li>仍无底图时确认脚本为完整模式（<code>data-mode="advanced"</code>）且站点可正常上报。</li>
</ol>
<h2>有底图但点击数据很少 / 为空</h2>
<ul>
<li>确认「点击 / 滚动」标签切换正确。</li>
<li>数据按设备分开：切到其他设备标签页查看。</li>
<li>流量本身少时，累计几天再看。</li>
</ul>
<h2>创建后一直不匹配任何数据</h2>
<details class="doc-faq"><summary>路径填写不一致</summary><div>检查创建的路径与真实页面路径是否一致（大小写、尾斜杠、参数拼写）。不确定时填写纯路径，覆盖面更广。</div></details>
<details class="doc-faq"><summary>同一页面有多个地址形态</summary><div>如 <code>/a</code> 与 <code>/a?from=x</code> 分别访问，用纯路径 <code>/a</code> 创建即可同时匹配两者。</div></details>
<details class="doc-faq"><summary>旧版本部署无底图</summary><div>早期版本存在热图检查接口读设置异常导致热图信息未下发的缺陷，请升级到最新版本后由新访客访问生成快照。</div></details>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 五、SEO 工具
            // ============================================================
            [
                'url' => 'seo-tools', 'title' => 'SEO 工具', 'icon' => 'search',
                'articles' => [
                    [
                        'url' => 'seo-tools-overview',
                        'title' => 'SEO 工具中心总览',
                        'desc' => '关键词追踪、外链监控、SEO 审计、批量工具四大模块的定位与入口。',
                        'content' => <<<'HTML'
<p>SEO 工具中心是 Monit 内置的搜索引擎优化工作台，包含四个模块：</p>
<table>
<thead><tr><th>模块</th><th>用途</th></tr></thead>
<tbody>
<tr><td><strong>关键词追踪</strong></td><td>监控目标关键词在搜索引擎中的排名变化趋势</td></tr>
<tr><td><strong>外链监控</strong></td><td>登记并验证指向你网站的外部链接，掌握外链质量</td></tr>
<tr><td><strong>SEO 审计</strong></td><td>对整站执行健康检查，输出评分、问题清单与 AI 优化建议</td></tr>
<tr><td><strong>批量工具</strong></td><td>DNS / Whois / SSL 等常用 SEO 日常小工具集合</td></tr>
</tbody>
</table>
<h2>入口与权限</h2>
<ul>
<li>登录后顶部导航进入「SEO 工具」。</li>
<li>各模块是否开放由管理员在系统设置 → SEO 中配置（审计、关键词、外链、工具可分别开关并设定配额）。</li>
</ul>
<h2>公开目录</h2>
<p>SEO 审计支持生成公开分享页（可选密码），可把审计报告直接发给客户查看，无需登录。</p>
<div class="doc-note doc-note--tip"><p><strong>建议工作流：</strong>先用「SEO 审计」体检整站 → 按 AI 建议修复 → 用「关键词追踪」验证排名变化 → 用「外链监控」维护链接资产。</p></div>
HTML,
                    ],
                    [
                        'url' => 'keyword-tracking',
                        'title' => '关键词排名追踪',
                        'desc' => '添加关键词、搜索引擎选择、快照与刷新机制、排名趋势解读。',
                        'content' => <<<'HTML'
<p>关键词追踪帮助你持续监控重要关键词在搜索引擎结果页（SERP）中的位置变化。</p>
<h2>添加关键词</h2>
<ol class="doc-steps">
<li>SEO 工具 → <strong>关键词追踪</strong> → 添加关键词。</li>
<li>填写关键词与要监测的域名。</li>
<li>选择搜索引擎（Google / Bing 等）与地区设置。</li>
<li>保存后系统按计划任务周期性抓取排名快照。</li>
</ol>
<h2>快照与刷新</h2>
<ul>
<li>排名数据以<strong>快照</strong>形式存储，由系统定时任务（cron）刷新。</li>
<li>刷新频率与次数受套餐/系统配额限制；手动「立即刷新」同样消耗配额。</li>
</ul>
<h2>趋势解读</h2>
<ul>
<li>列表展示每个关键词的最新排名与相对上次的变化（升 / 降 / 持平）。</li>
<li>点击进入详情可查看历史快照曲线，配合你的优化动作复盘效果。</li>
</ul>
<h2>常见问题</h2>
<details class="doc-faq"><summary>排名一直没有数据</summary><div>确认域名与关键词拼写；部分搜索引擎对自动化抓取限流，个别快照可能延迟，等待下一轮刷新。</div></details>
<details class="doc-faq"><summary>排名波动大</summary><div>搜索引擎本身存在地域与个性化差异，建议观察多日趋势而非单点数值。</div></details>
HTML,
                    ],
                    [
                        'url' => 'backlinks-monitor',
                        'title' => '外链监控',
                        'desc' => '添加外链、验证机制、导出外链清单，维护链接资产质量。',
                        'content' => <<<'HTML'
<p>外链（反向链接）是 SEO 排名的重要信号。外链监控让你把分散在外的链接资产集中管理并定期验证。</p>
<h2>添加外链</h2>
<ol class="doc-steps">
<li>SEO 工具 → <strong>外链监控</strong> → 添加外链。</li>
<li>填写外链来源页地址与你网站的目标地址。</li>
<li>保存后系统会验证来源页是否真实存在指向你网站的链接。</li>
</ol>
<h2>验证机制</h2>
<ul>
<li>添加时自动验证一次；之后由计划任务周期性复检。</li>
<li>验证结果标记为<strong>有效 / 失效</strong>：来源页被删改、加了 <code>nofollow</code> 或无法访问都会影响状态。</li>
</ul>
<h2>导出</h2>
<p>外链清单支持一键导出 CSV，方便在别的工具中继续分析或交给外链团队维护。</p>
<h2>常见问题</h2>
<details class="doc-faq"><summary>添加后一直显示验证中</summary><div>来源页可能加载缓慢或使用了反爬机制；稍后手动重新验证一次。</div></details>
<details class="doc-faq"><summary>有效外链变失效</summary><div>来源页面可能已删除链接或整页 404；用导出的清单逐条跟进恢复或替换。</div></details>
HTML,
                    ],
                    [
                        'url' => 'seo-audit-guide',
                        'title' => 'SEO 审计：全站体检、AI 建议、对比与分享',
                        'desc' => '发起全站审计、评分与问题清单、AI 优化建议、审计对比、PDF 导出与公开分享。',
                        'content' => <<<'HTML'
<p>SEO 审计对目标站点做一次系统化体检：从标题、描述、结构化数据、链接结构、性能等维度逐项检查，输出整体评分与问题清单。</p>
<h2>发起审计</h2>
<ol class="doc-steps">
<li>SEO 工具 → <strong>SEO 审计</strong> → 新建审计。</li>
<li>输入要审计的域名（或从「我的网站」选择）。</li>
<li>提交后系统开始抓取整站页面，完成后生成报告。</li>
</ol>
<h2>报告内容</h2>
<ul>
<li><strong>整体评分</strong>：全站 SEO 健康分。</li>
<li><strong>问题清单</strong>：按严重程度分组（缺标题、描述过长、断链、重复内容等），可逐条查看命中页面。</li>
<li><strong>AI 优化建议</strong>：在配置了 AI 服务（管理员设置）的实例上，会针对问题给出可执行的修复建议文案。</li>
</ul>
<h2>对比 / 导出 / 分享</h2>
<ul>
<li><strong>审计对比</strong>：选择两次审计报告对比，量化修复效果。</li>
<li><strong>导出</strong>：支持导出报告数据与下载 PDF 版本。</li>
<li><strong>公开分享</strong>：生成只读分享链接（可另设访问密码），客户无需登录即可查看；分享可随时关闭。手动「刷新」会重新抓取并更新报告。</li>
</ul>
<div class="doc-note doc-note--warn"><p><strong>配额提示：</strong>审计消耗抓取资源，系统对审计数量与频率有限额（管理员可调）；达到限额时请等待自动重置或联系管理员。</p></div>
HTML,
                    ],
                    [
                        'url' => 'seo-tools-batch',
                        'title' => '批量 SEO 小工具',
                        'desc' => '工具中心的 DNS / Whois / SSL 等日常查询工具的用法。',
                        'content' => <<<'HTML'
<p>工具中心收录了一批 SEO 日常高频小工具，输入域名即可查询，适合快速排查与技术性检查。</p>
<h2>使用方式</h2>
<ol class="doc-steps">
<li>进入 SEO 工具 → <strong>工具中心</strong>。</li>
<li>选择需要的工具卡片（DNS 查询、Whois、SSL 证书、HTTP 头检查等）。</li>
<li>输入域名提交，结果即时展示在页面中。</li>
</ol>
<h2>适用场景</h2>
<ul>
<li>网站解析变更后确认 DNS 是否生效。</li>
<li>检查域名注册信息与到期时间。</li>
<li>上线前核对 SSL 证书链与有效期。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>提示：</strong>这些工具为即时查询不产生历史记录；需要长期跟踪的指标请使用关键词追踪与外链监控。</p></div>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 六、套餐与支付
            // ============================================================
            [
                'url' => 'billing-payments', 'title' => '套餐与支付', 'icon' => 'credit',
                'articles' => [
                    [
                        'url' => 'plans-quotas',
                        'title' => '套餐与配额说明（0 = 关闭，-1 = 不限量）',
                        'desc' => '套餐体系、各功能配额的统一语义、免费/访客套餐的默认限制。',
                        'content' => <<<'HTML'
<p>Monit 的功能使用量由「套餐 + 配额」控制，全部配额遵循统一语义：</p>
<table>
<thead><tr><th>取值</th><th>含义</th></tr></thead>
<tbody>
<tr><td><code>0</code></td><td><strong>该功能关闭</strong>（不可用）</td></tr>
<tr><td><code>-1</code></td><td><strong>不限量</strong></td></tr>
<tr><td>未设置</td><td><strong>视为不限量</strong>（按系统默认放开）</td></tr>
<tr><td>正整数</td><td>该功能的用量上限</td></tr>
</tbody>
</table>
<h2>常见配额项</h2>
<ul>
<li>可创建网站数、每月事件数、数据保留期。</li>
<li>会话回放条数、热图数量。</li>
<li>SEO：关键词数、外链数、审计次数等。</li>
</ul>
<h2>三类内置套餐</h2>
<ul>
<li><strong>访客（guest）</strong>：未登录访客的试用边界，由管理员配置。</li>
<li><strong>免费（free）</strong>：注册用户的默认套餐，含基础配额。</li>
<li><strong>付费套餐</strong>：由管理员创建，可自定义价格（月付/年付）与全部配额。</li>
</ul>
<div class="doc-note doc-note--info"><p><strong>查看当前用量：</strong>套餐页（「套餐」菜单）展示当前套餐与各配额的已用/上限。</p></div>
HTML,
                    ],
                    [
                        'url' => 'purchase-payment',
                        'title' => '购买套餐与支付方式',
                        'desc' => '套餐购买流程、月付/年付、支持的支付渠道、离线转账与支付凭证。',
                        'content' => <<<'HTML'
<h2>购买流程</h2>
<ol class="doc-steps">
<li>进入「套餐」页对比各套餐功能与价格。</li>
<li>选择套餐与计费周期（<strong>月付 / 年付</strong>，默认周期由站点配置）。</li>
<li>选择可用的支付方式完成支付，成功后套餐立即生效。</li>
</ol>
<h2>支付方式</h2>
<p>支付方式由站点管理员配置，通常包括：Stripe（银行卡）、PayPal、支付宝、微信支付、Razorpay、Mollie 等 20 余种渠道；实际展示以结账页可选列表为准（未启用的渠道不会出现）。</p>
<h2>离线转账（如启用）</h2>
<ol class="doc-steps">
<li>结账页选择「离线支付 / 银行转账」。</li>
<li>按页面给出的收款信息完成转账。</li>
<li>回到订单页提交<strong>支付凭证</strong>，等待管理员人工确认开通。</li>
</ol>
<h2>支付后的状态确认</h2>
<ul>
<li>支付成功会跳转到「感谢」页，套餐与配额即时更新。</li>
<li>如已扣款但套餐未生效（极少数网络回调延迟），等待数分钟仍未生效请联系站点客服或提交工单。</li>
</ul>
<div class="doc-note doc-note--info"><p><strong>价格与货币：</strong>以结账页实际显示为准，站点可配置展示货币与汇率规则。</p></div>
HTML,
                    ],
                    [
                        'url' => 'invoices-redeem',
                        'title' => '发票、支付记录与兑换码',
                        'desc' => '查看与下载发票、支付历史、兑换码的使用方式。',
                        'content' => <<<'HTML'
<h2>支付记录与发票</h2>
<ul>
<li>「账户 → 支付记录」列出全部历史订单：金额、周期、状态与时间。</li>
<li>已支付订单可下载/打印<strong>发票</strong>（PDF），发票抬头企业信息由站点配置；如抬头有误，可联系管理员修改后重新开具。</li>
</ul>
<h2>兑换码</h2>
<p>管理员可以签发兑换码（指定套餐、有效期与次数），用户按以下方式使用：</p>
<ol class="doc-steps">
<li>「账户 → 套餐」或「支付」页找到<strong>兑换码</strong>入口。</li>
<li>输入兑换码提交。</li>
<li>校验通过后账户立即切换到对应套餐（不影响已产生的数据）。</li>
</ol>
<details class="doc-faq"><summary>兑换码提示无效</summary><div>检查拼写（区分大小写）、是否已被使用、是否过期或被停用。</div></details>
<details class="doc-faq"><summary>兑换码与当前付费套餐冲突</summary><div>兑换通常直接覆盖当前套餐；有疑问请先咨询管理员再兑换。</div></details>
HTML,
                    ],
                    [
                        'url' => 'upgrade-downgrade-cancel',
                        'title' => '升级、降级与取消订阅',
                        'desc' => '套餐变更的生效规则、周期订阅的取消方式、常见疑问。',
                        'content' => <<<'HTML'
<h2>升级套餐</h2>
<ol class="doc-steps">
<li>「套餐」页选择更高档套餐 → 按差价完成支付。</li>
<li>支付成功后立即生效，全部配额同步提升；已有数据不受影响。</li>
</ol>
<h2>降级 / 更换套餐</h2>
<ul>
<li>选择较低档套餐并完成支付后切换生效。</li>
<li>若当前用量超过新套餐配额（如网站数、回放条数），超出部分的功能会在清理前受限：<strong>不再新增</strong>，直到用量回落到新配额内。</li>
</ul>
<h2>取消订阅</h2>
<ul>
<li>在「套餐 / 订阅」管理页对当前周期订阅执行<strong>取消</strong>：本计费周期内仍可正常使用，到期后不再续费。</li>
<li>取消后如需恢复，重新下单即可，数据在保留期内不会丢失。</li>
</ul>
<h2>常见问题</h2>
<details class="doc-faq"><summary>升级后价格如何计算</summary><div>以结账页展示的金额为准；站点可选择按剩余周期折算或按新周期计费。</div></details>
<details class="doc-faq"><summary>取消订阅会立刻失去功能吗</summary><div>不会，已支付的周期内功能保持可用，到期后回落到免费/访客套餐配额。</div></details>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 七、推荐计划
            // ============================================================
            [
                'url' => 'affiliate-program', 'title' => '推荐计划', 'icon' => 'share',
                'articles' => [
                    [
                        'url' => 'affiliate-rules',
                        'title' => '推荐计划规则：佣金比例 / Cookie 有效期 / 最低提现',
                        'desc' => '联盟计划的参与方式、佣金计算规则与关键参数说明。',
                        'content' => <<<'HTML'
<p>推荐（联盟）计划邀请你把 Monit 推荐给其他人：被推荐用户产生付费后，你可以获得订单金额一定比例的佣金。</p>
<h2>关键参数</h2>
<p>以下参数由站点管理员配置，具体数值以「推荐计划」页展示为准：</p>
<table>
<thead><tr><th>参数</th><th>说明</th></tr></thead>
<tbody>
<tr><td>佣金比例</td><td>每笔被推荐订单可获得的百分比</td></tr>
<tr><td>Cookie 有效期</td><td>访客通过你的推广链接注册后，归属关系的有效天数（默认 30 天）</td></tr>
<tr><td>最低提现金额</td><td>佣金余额达到该值后才可发起提现（默认 50）</td></tr>
</tbody>
</table>
<h2>参与方式</h2>
<ul>
<li>登录后进入「推荐」页面，系统自动为你的账户生成专属推广链接。</li>
<li>被推荐用户需通过你的链接注册（在 Cookie 有效期内）才计入归属。</li>
</ul>
<div class="doc-note doc-note--warn"><p><strong>合规要求：</strong>请勿使用垃圾邮件、虚假宣传等方式推广；违规账户的佣金与提现可能被冻结。</p></div>
HTML,
                    ],
                    [
                        'url' => 'affiliate-withdraw',
                        'title' => '推广链接、佣金明细与提现',
                        'desc' => '获取推广链接、查看被推荐用户与佣金明细、发起提现的流程。',
                        'content' => <<<'HTML'
<h2>获取推广链接</h2>
<p>「推荐」页展示你的专属链接（形如 <code>/register?ref=你的推荐码</code>），支持一键复制。将其用于文章、社交媒体或朋友圈均可。</p>
<h2>佣金明细</h2>
<ul>
<li><strong>被推荐用户</strong>：通过你注册的用户列表与状态。</li>
<li><strong>佣金记录</strong>：每笔订单产生的佣金金额与状态（待确认 / 可提现）。</li>
<li>佣金在被推荐订单支付确认后入账。</li>
</ul>
<h2>提现流程</h2>
<ol class="doc-steps">
<li>「推荐 → 提现」页面查看当前可提现余额。</li>
<li>余额达到<strong>最低提现金额</strong>后，发起提现并填写收款方式。</li>
<li>管理员审核打款后，提现记录更新为已完成。</li>
</ol>
<details class="doc-faq"><summary>为什么提现按钮不可用</summary><div>余额未达到最低提现金额，或存在待确认的佣金记录；达标后即可发起。</div></details>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 八、账户与安全
            // ============================================================
            [
                'url' => 'account-security', 'title' => '账户与安全', 'icon' => 'user',
                'articles' => [
                    [
                        'url' => 'account-profile-preferences',
                        'title' => '账户资料与偏好设置',
                        'desc' => '修改个人资料、界面偏好（主题/时区等）、通知偏好与邮箱。',
                        'content' => <<<'HTML'
<p>「账户」页集中管理个人资料、界面偏好与安全设置。</p>
<h2>个人资料</h2>
<ul>
<li>修改姓名与登录邮箱；更换邮箱需要通过验证。</li>
<li>可随时修改密码（需输入当前密码确认）。</li>
</ul>
<h2>偏好设置</h2>
<ul>
<li><strong>界面主题</strong>：浅色 / 深色 / 跟随系统。</li>
<li><strong>时区</strong>：影响统计页时间显示与报表分组口径。</li>
<li><strong>通知偏好</strong>：选择接收哪些站内/邮件通知（详见《站内通知与邮件通知》）。</li>
</ul>
<h2>登录日志</h2>
<p>「账户 → 登录日志」记录每次登录的时间、IP 与设备，发现异常登录请立即修改密码并开启两步验证。</p>
<h2>删除账户</h2>
<div class="doc-note doc-note--warn"><p><strong>删除账户为不可逆操作</strong>：名下网站与统计数据将被删除。如只是不再使用部分功能，建议降级到免费套餐而非删除。</p></div>
HTML,
                    ],
                    [
                        'url' => 'twofa-guide',
                        'title' => '两步验证（2FA）',
                        'desc' => '用验证器 App 开启 TOTP 两步验证，以及关闭 2FA 的方法。',
                        'content' => <<<'HTML'
<p>两步验证（2FA）为账户增加一层保护：登录时除密码外还需输入验证器 App 生成的动态验证码。</p>
<h2>开启 2FA</h2>
<ol class="doc-steps">
<li>「账户 → 安全」中找到<strong>两步验证</strong>，点击开启。</li>
<li>用验证器 App（Google Authenticator、1Password、Aegis 等）扫描页面二维码，或手动输入密钥。</li>
<li>输入 App 上显示的 6 位验证码完成绑定。</li>
<li>妥善保存页面给出的<strong>恢复码</strong>（手机丢失时可用于登录）。</li>
</ol>
<h2>登录验证</h2>
<p>开启后，每次登录在输入密码后进入 2FA 验证页，输入验证器当前验证码即可。</p>
<h2>关闭 2FA</h2>
<ul>
<li>登录后进入账户安全页，输入当前验证码（或密码）确认后关闭。</li>
<li>无法登录（手机丢失）时使用恢复码登录，或通过客服验证身份后由管理员重置。</li>
</ul>
<details class="doc-faq"><summary>验证码总是不通过</summary><div>检查手机系统时间是否自动同步；TOTP 对时间敏感，时间偏差会导致验证码错误。</div></details>
HTML,
                    ],
                    [
                        'url' => 'phone-binding',
                        'title' => '绑定手机号与短信服务',
                        'desc' => '绑定手机号的操作流程、验证码收不到的排查、短信重置密码。',
                        'content' => <<<'HTML'
<p>绑定手机号后，可以使用短信验证码重置密码，账户多一道保障。该功能依赖站点已开通短信服务（由管理员配置）。</p>
<h2>绑定流程</h2>
<ol class="doc-steps">
<li>「账户」页找到<strong>手机绑定</strong>，填写手机号。</li>
<li>点击「发送验证码」，输入收到的短信验证码确认。</li>
<li>绑定成功后手机号显示在账户资料中（可更换或解绑）。</li>
</ol>
<h2>收不到验证码</h2>
<details class="doc-faq"><summary>检查号码与格式</summary><div>确认选择了正确的国家区号、号码无误；部分运营商拦截验证类短信，可联系运营商解除。</div></details>
<details class="doc-faq"><summary>发送频率限制</summary><div>验证码发送有频率与次数限制，稍等片刻再试。</div></details>
<details class="doc-faq"><summary>提示短信功能未开通</summary><div>说明站点管理员尚未配置短信通道，请联系管理员。</div></details>
<h2>短信重置密码</h2>
<p>忘记密码时，在登录页选择「短信找回」：输入绑定手机号 → 接收验证码 → 设置新密码。</p>
HTML,
                    ],
                    [
                        'url' => 'api-keys',
                        'title' => 'API 密钥与开放接口',
                        'desc' => '生成/重置/吊销 API 密钥，以及调用统计 API 的基本方式。',
                        'content' => <<<'HTML'
<p>API 密钥让你通过程序访问自己的统计数据（如导出到其他系统、自建看板）。</p>
<h2>管理密钥</h2>
<ol class="doc-steps">
<li>「账户 → API」页面查看当前密钥状态。</li>
<li><strong>生成</strong>：未生成时点击生成，得到个人 API Key。</li>
<li><strong>重置</strong>：怀疑泄露时重置，旧密钥立即失效。</li>
<li><strong>吊销</strong>：不再使用时吊销，所有 API 调用将被拒绝。</li>
</ol>
<div class="doc-note doc-note--warn"><p><strong>密钥等同于账户凭证：</strong>请勿提交到代码仓库或公开页面；通过环境变量保存。</p></div>
<h2>调用方式</h2>
<p>请求统计接口时在请求头携带 API Key，完整接口清单见站点「API 文档」页（<code>/api/docs</code>）。</p>
<pre><code>curl "https://你的Monit域名/api/..." \
  -H "Authorization: Bearer 你的API密钥"</code></pre>
HTML,
                    ],
                    [
                        'url' => 'teams-guide',
                        'title' => '团队协作：邀请成员与角色',
                        'desc' => '创建团队、邀请成员、移除成员、成员接受邀请的流程。',
                        'content' => <<<'HTML'
<p>团队功能让你与同事共同管理网站与统计数据，无需共享账户。</p>
<h2>创建团队与邀请</h2>
<ol class="doc-steps">
<li>「团队」页点击<strong>创建团队</strong>，填写团队名称。</li>
<li>进入团队 → 输入成员邮箱<strong>发送邀请</strong>。</li>
<li>对方登录后接受邀请（或通过邀请邮件中的链接）加入团队。</li>
</ol>
<h2>成员管理</h2>
<ul>
<li>团队页可查看成员列表与加入时间。</li>
<li>移除成员后，其立即失去该团队内网站的访问权限（账户本身不受影响）。</li>
</ul>
<h2>权限说明</h2>
<p>团队内的网站归属与统计对成员可见；成员的个人网站与账户资料相互隔离。</p>
<div class="doc-note doc-note--tip"><p><strong>建议：</strong>按项目拆分团队（一个项目一个团队），避免权限范围过大。</p></div>
HTML,
                    ],
                    [
                        'url' => 'account-security-faq',
                        'title' => '登录与账号安全常见问题',
                        'desc' => '收不到激活邮件、忘记密码、社交登录、SSO 与账户安全建议。',
                        'content' => <<<'HTML'
<h2>注册与激活</h2>
<details class="doc-faq"><summary>收不到激活邮件</summary><div>检查垃圾箱；管理员侧未配置 SMTP 时邮件可能无法发出，请联系管理员。激活链接有有效期，过期可在登录页重新发送。</div></details>
<h2>登录与找回</h2>
<details class="doc-faq"><summary>忘记密码</summary><div>登录页「忘记密码」通过注册邮箱重置；已绑定手机号可用短信重置。</div></details>
<details class="doc-faq"><summary>可以使用第三方账号登录吗</summary><div>站点开启社交登录（Google / GitHub 等，由管理员配置）时，登录页会显示对应按钮；绑定后可用第三方账号快速登录。</div></details>
<h2>安全建议</h2>
<ul>
<li>使用独立强密码并开启<strong>两步验证</strong>。</li>
<li>定期查看「登录日志」中的异常 IP。</li>
<li>团队成员离职时及时在团队中移除，必要时重置 API 密钥。</li>
</ul>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 九、通知与工单
            // ============================================================
            [
                'url' => 'notifications-tickets', 'title' => '通知与工单', 'icon' => 'bell',
                'articles' => [
                    [
                        'url' => 'notifications-guide',
                        'title' => '站内通知与邮件通知',
                        'desc' => '站内铃铛通知与邮件通知的类型、订阅退订与收不到邮件的排查。',
                        'content' => <<<'HTML'
<p>通知系统覆盖两类渠道：</p>
<ul>
<li><strong>站内通知</strong>：登录后顶部铃铛图标查看，支持标记已读与删除。</li>
<li><strong>邮件通知</strong>：重要事件（支付成功、工单回复、套餐到期提醒等）发送到注册邮箱；可在「账户 → 偏好设置」按类型订阅/退订。</li>
</ul>
<h2>常见通知类型</h2>
<ul>
<li>账户与安全：登录提醒、密码修改。</li>
<li>支付与套餐：支付成功、订阅到期提醒。</li>
<li>工单：客服回复工单时通知你。</li>
</ul>
<h2>退订</h2>
<p>营销类邮件底部带「一键退订」链接；事务类邮件（安全、支付）为保障账户安全不提供退订。</p>
<div class="doc-note doc-note--tip"><p><strong>收不到邮件？</strong>检查垃圾箱；站点 SMTP 故障时请联系管理员。</p></div>
HTML,
                    ],
                    [
                        'url' => 'tickets-guide',
                        'title' => '工单系统：提交、回复与邮件直回',
                        'desc' => '向站点客服提交工单、在工单内回复、直接回复邮件自动归档（TK 编号）。',
                        'content' => <<<'HTML'
<p>遇到问题可在站内直接向客服提交工单，全过程有记录可查。</p>
<h2>提交工单</h2>
<ol class="doc-steps">
<li>「工单」页点击<strong>新建工单</strong>。</li>
<li>填写标题、描述问题（可附截图链接与复现步骤）。</li>
<li>提交后可随时在工单列表查看处理状态。</li>
</ol>
<h2>回复工单</h2>
<ul>
<li>进入工单详情在底部输入框回复。</li>
<li><strong>邮件直回</strong>：客服回复会发邮件通知你；直接回复该邮件，内容会自动归档到对应工单（邮件主题中的 <code>TK-编号</code> 用于匹配）。</li>
</ul>
<h2>状态说明</h2>
<table>
<thead><tr><th>状态</th><th>含义</th></tr></thead>
<tbody>
<tr><td>待处理</td><td>已提交，等待客服响应</td></tr>
<tr><td>处理中</td><td>客服已响应，等待双方沟通</td></tr>
<tr><td>已关闭</td><td>问题解决；如有新问题请新建工单</td></tr>
</tbody>
</table>
HTML,
                    ],
                ],
            ],

            // ============================================================
            // 十、管理员指南
            // ============================================================
            [
                'url' => 'admin-guide', 'title' => '管理员指南', 'icon' => 'cog',
                'articles' => [
                    [
                        'url' => 'admin-overview',
                        'title' => '管理后台总览',
                        'desc' => '管理员后台的结构：仪表盘、用户/网站/支付管理、系统设置与内容维护。',
                        'content' => <<<'HTML'
<p>管理员（<code>type = 1</code>）登录后可访问「管理后台」，负责整个站点的运营配置。</p>
<h2>后台结构</h2>
<table>
<thead><tr><th>模块</th><th>内容</th></tr></thead>
<tbody>
<tr><td>仪表盘</td><td>平台增长、用户、支付、数据库统计</td></tr>
<tr><td>用户管理</td><td>用户增删改、封禁/解封、登录日志、手动开通套餐</td></tr>
<tr><td>网站管理</td><td>全站网站列表与状态控制</td></tr>
<tr><td>支付管理</td><td>订单列表、手动创建支付、发票与红冲（credit note）</td></tr>
<tr><td>系统设置</td><td>40+ 个设置分组（见《系统设置详解》）</td></tr>
<tr><td>内容维护</td><td>公告、广播、博客、自定义页面、帮助中心、语言包</td></tr>
<tr><td>运营工具</td><td>套餐、税率、兑换码、插件、许可证、日志</td></tr>
</tbody>
</table>
<h2>日常运维节奏建议</h2>
<ul>
<li><strong>每日</strong>：仪表盘看新增用户与支付；工单清零。</li>
<li><strong>每周</strong>：检查 cron 心跳、日志错误、备份。</li>
<li><strong>每月</strong>：GeoIP 数据库更新、套餐价格与配额复盘。</li>
</ul>
HTML,
                    ],
                    [
                        'url' => 'admin-settings-guide',
                        'title' => '系统设置详解（全部设置分组）',
                        'desc' => '管理后台系统设置的全部分组逐项说明：通用、支付、统计、邮件、安全、主题、内容与套餐。',
                        'content' => <<<'HTML'
<p>「管理后台 → 系统设置」按分组管理全部站点配置。以下按侧栏顺序列出各分组的职责与关键项。</p>
<h2>基础与账户</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>main（通用）</td><td>站点开关、注册开关、默认语言、邮箱验证、维护模式、IP 排除等</td></tr>
<tr><td>users（用户）</td><td>注册默认套餐、用户相关开关</td></tr>
<tr><td>branding（品牌）</td><td>站点名称、Logo、Favicon、品牌色</td></tr>
<tr><td>theme（主题）</td><td>界面主题与配色模式</td></tr>
<tr><td>custom / custom_images</td><td>自定义 CSS/JS 注入与图片资源</td></tr>
</tbody>
</table>
<h2>统计与分析</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>analytics（统计）</td><td>热图开关、<strong>会话回放全局开关与配额</strong>（-1 不限量 / 0 关闭）等统计功能级控制</td></tr>
<tr><td>maps（地图）</td><td>地理展示用的地图服务配置</td></tr>
<tr><td>seo（SEO 功能）</td><td>审计、关键词、外链、工具中心各模块开关与配额</td></tr>
<tr><td>ai（AI 服务）</td><td>AI 提供商与密钥（驱动审计 AI 建议、AI 洞察）</td></tr>
</tbody>
</table>
<h2>支付与商务</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>payment（支付）</td><td>展示货币、<strong>默认支付周期</strong>、支付方式开关、发票设置</td></tr>
<tr><td>payment_gateways（支付网关）</td><td>各网关凭据写入 .env（Stripe/PayPal/Razorpay…）</td></tr>
<tr><td>plan_free / plan_guest / plan_custom</td><td>免费/访客/自定义套餐的配额与价格（0=关闭，-1=不限量）</td></tr>
<tr><td>affiliate（推荐计划）</td><td>佣金比例、Cookie 有效期、最低提现、开关</td></tr>
<tr><td>business（企业信息）</td><td>发票抬头企业资料</td></tr>
</tbody>
</table>
<h2>消息与安全</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>smtp（邮件）</td><td>发件服务器配置（未配置则全站发信失败）</td></tr>
<tr><td>sms（短信）</td><td>短信通道与<strong>使用场景开关</strong>（注册/登录/绑定手机/重置密码，如 phone_bind）</td></tr>
<tr><td>captcha（验证码）</td><td>reCAPTCHA / hCaptcha / Geetest 及各表单启用位</td></tr>
<tr><td>socials（社交登录）</td><td>Google / GitHub 等第三方登录开关与凭据</td></tr>
<tr><td>cookie_consent</td><td>Cookie 同意横幅文案与行为</td></tr>
<tr><td>tickets（工单）</td><td>工单系统开关与<strong>邮件入站 webhook token</strong></td></tr>
</tbody>
</table>
<h2>内容与通知</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>announcements（公告）</td><td>全站顶部公告条</td></tr>
<tr><td>internal_notifications / email_notifications</td><td>站内/邮件通知的启用位</td></tr>
<tr><td>webhooks（webhook）</td><td>对外推送事件回调地址</td></tr>
<tr><td>content（内容）</td><td>博客、帮助中心、页面等内容区开关</td></tr>
<tr><td>ads（广告位）</td><td>各位置广告代码</td></tr>
</tbody>
</table>
<h2>进阶</h2>
<table>
<thead><tr><th>分组</th><th>内容</th></tr></thead>
<tbody>
<tr><td>offload（对象存储）</td><td>静态资源卸载到 S3 兼容存储</td></tr>
<tr><td>image_optimizer</td><td>图片压缩优化</td></tr>
<tr><td>email_shield</td><td>邮箱地址防抓取混淆</td></tr>
<tr><td>dynamic_og_images</td><td>动态 OG 分享图生成</td></tr>
<tr><td>pwa / push_notifications</td><td>PWA 清单与 Web Push 推送</td></tr>
<tr><td>cron（计划任务）</td><td>任务状态与心跳检查</td></tr>
</tbody>
</table>
<div class="doc-note doc-note--warn"><p><strong>排障经验：</strong>设置按分组按需写入数据库，分组对象可能只包含部分键。读取任何设置子键时务必先判断存在性（isset/默认值），否则可能抛出 Undefined property 异常并被上层 catch 吞掉，表现为「功能静默失效」（典型：热图/回放采集信息未下发）。</p></div>
HTML,
                    ],
                    [
                        'url' => 'admin-users-plans',
                        'title' => '用户与套餐管理',
                        'desc' => '后台创建/编辑用户、封禁、手动开通套餐、套餐与兑换码管理。',
                        'content' => <<<'HTML'
<h2>用户管理</h2>
<ul>
<li><strong>创建用户</strong>：填写姓名、邮箱、密码与初始套餐。</li>
<li><strong>编辑</strong>：修改资料、更换套餐、调整状态。</li>
<li><strong>封禁/解封</strong>：封禁后用户立即无法登录，数据保留。</li>
<li><strong>登录日志</strong>：查看某用户的登录记录排查异常。</li>
</ul>
<h2>手动开通套餐</h2>
<ol class="doc-steps">
<li>进入用户编辑页，把套餐切换为目标套餐。</li>
<li>保存立即生效（常用于线下收款后人工开通、补偿赠予）。</li>
</ol>
<h2>套餐管理（plans）</h2>
<ul>
<li>创建套餐：指定 plan_id、名称、<strong>月付/年付价格</strong>与全部配额。</li>
<li>价格数组结构：<code>monthly</code> 与 <code>yearly</code>（年付价默认为月付 × 12）。</li>
<li>上下架用状态开关控制；删除前确认没有用户仍在该套餐。</li>
</ul>
<h2>兑换码（codes）</h2>
<ul>
<li>批量签发：指定套餐、有效次数与过期时间。</li>
<li>「已兑换」列表可审计每个码的使用情况。</li>
</ul>
<div class="doc-note doc-note--info"><p><strong>配额语义再次强调：</strong>0 = 关闭该功能，-1 = 不限量，未设置 = 视为不限量。</p></div>
HTML,
                    ],
                    [
                        'url' => 'geoip-config',
                        'title' => 'GeoIP 数据库配置（国家 / 城市）',
                        'desc' => '配置 mmdb 数据库文件路径、下载 db-ip 免费库、更新与验证。',
                        'content' => <<<'HTML'
<p>统计中的国家与城市维度依赖本地 GeoIP（mmdb）数据库解析访客 IP。未配置或文件缺失时地理维度为空。</p>
<h2>文件位置</h2>
<p>默认路径为 <code>storage/app/geoip/country.mmdb</code>，可通过环境变量 <code>GEOIP_MMDB_PATH</code> 覆盖（见 <code>config/services.php</code> 的 <code>geoip</code> 段）。</p>
<h2>下载免费数据库</h2>
<p>推荐 db-ip.com 的免费 lite 库（每月更新）：</p>
<pre><code># 国家库
curl -L https://download.db-ip.com/free/dbip-country-lite-$(date +%Y-%m).mmdb.gz \
  | gunzip &gt; storage/app/geoip/country.mmdb

# 需要城市维度时，用城市库替换同一路径
curl -L https://download.db-ip.com/free/dbip-city-lite-$(date +%Y-%m).mmdb.gz \
  | gunzip &gt; storage/app/geoip/country.mmdb</code></pre>
<div class="doc-note doc-note--info"><p><strong>兼容性：</strong>MaxMind GeoLite2 格式同样可用。要展示城市级报表请使用 <strong>city</strong> 版本库；country 库只有国家级精度。</p></div>
<h2>更新与验证</h2>
<ul>
<li>lite 库每月发布，建议配置每月一次的更新脚本重跑上述 curl 命令。</li>
<li>更新后无需重启队列；新的访客数据立即按新库解析。</li>
<li>验证：访问一次站点后，统计页「国家」卡片应出现数据。</li>
</ul>
<h2>常见问题</h2>
<details class="doc-faq"><summary>国家有数据、城市没有</summary><div>使用的是 country 版 mmdb；换成 city lite 库即可。</div></details>
<details class="doc-faq"><summary>本地访问显示未知</summary><div>内网/回环地址不在 GeoIP 库中，属正常现象。</div></details>
HTML,
                    ],
                    [
                        'url' => 'admin-payments-taxes',
                        'title' => '支付、税务与发票管理',
                        'desc' => '启用支付网关、配置货币与默认周期、税率库与发票、人工确认离线支付。',
                        'content' => <<<'HTML'
<h2>启用支付渠道</h2>
<ol class="doc-steps">
<li>「系统设置 → 支付」中打开要使用的支付方式开关，设置展示货币与<strong>默认支付周期</strong>。</li>
<li>「支付网关」页填写各网关凭据（保存后写入 <code>.env</code>，如 Stripe Key、PayPal Client 等）。</li>
<li>在前台结账页验证：只应出现已启用的渠道，默认选中即为设置的默认周期。</li>
</ol>
<div class="doc-note doc-note--warn"><p><strong>回调地址：</strong>每个网关需在其后台配置对应 webhook（<code>/webhooks/stripe</code>、<code>/webhooks/paypal</code> 等），否则支付状态无法自动确认。</p></div>
<h2>税率（taxes）</h2>
<ul>
<li>支持按国家/地区配置税率，可手动添加或批量导入。</li>
<li>税率在结账时按用户账单地址自动附加。</li>
</ul>
<h2>支付管理（后台）</h2>
<ul>
<li><strong>订单列表</strong>：全部支付记录与状态，支持查看详情。</li>
<li><strong>手动创建支付</strong>：为指定用户登记线下收款（选择用户、套餐、金额）。</li>
<li><strong>发票 / 红冲（credit note）</strong>：订单可开具发票；错账可开具红字凭证冲销。</li>
<li><strong>离线支付凭证</strong>：用户提交的转账凭证在此人工审核确认开通。</li>
</ul>
HTML,
                    ],
                    [
                        'url' => 'admin-smtp-sms-captcha',
                        'title' => '邮件（SMTP）、短信与验证码配置',
                        'desc' => 'SMTP 发信配置与排障、短信通道与场景开关、验证码服务的启用。',
                        'content' => <<<'HTML'
<h2>SMTP 邮件</h2>
<ol class="doc-steps">
<li>「系统设置 → SMTP」填写主机、端口、加密方式、账号密码与发件人。</li>
<li>保存后用「发送测试邮件」验证连通性。</li>
</ol>
<div class="doc-note doc-note--warn"><p><strong>未配置 SMTP 时</strong>：激活邮件、密码重置、工单通知等全部邮件功能失效——这是「收不到激活邮件」的最常见根因。</p></div>
<h2>短信（sms）</h2>
<ul>
<li>配置短信通道凭据后，还需按<strong>场景</strong>启用：注册、登录、<strong>手机绑定（phone_bind）</strong>、重置密码等。</li>
<li>用户反馈「绑定手机提示未开通」时，检查对应场景开关是否打开。</li>
</ul>
<h2>验证码（captcha）</h2>
<ul>
<li>支持 reCAPTCHA / hCaptcha / Geetest，填入站点密钥后可按表单分别启用（注册、登录、联系表单等）。</li>
<li>启用后前端表单出现人机验证组件；密钥错误会导致验证失败报错。</li>
</ul>
<h2>工单邮件入站</h2>
<p>「工单设置」保存后生成 webhook token：把邮件服务商的入站回调指向 <code>/webhooks/email</code>，即可实现「客服回邮件 → 自动归档进工单」。</p>
HTML,
                    ],
                    [
                        'url' => 'admin-content-help',
                        'title' => '内容维护：公告、页面、帮助中心与语言',
                        'desc' => '公告条、广播、博客、自定义页面、帮助中心分类/文章与语言包的维护方法。',
                        'content' => <<<'HTML'
<h2>公告与广播</h2>
<ul>
<li><strong>公告（announcements）</strong>：全站顶部公告条，适合维护通知、活动入口；可设定生效时间。</li>
<li><strong>广播（broadcasts）</strong>：向全部或指定用户群发站内/邮件消息，支持复制历史广播。</li>
</ul>
<h2>博客与自定义页面</h2>
<ul>
<li><strong>博客</strong>：发布 SEO 文章，前台 <code>/blog</code> 展示。</li>
<li><strong>自定义页面（pages）</strong>：条款、隐私政策等静态页，前台 <code>/page/{url}</code> 访问，<code>/pages</code> 为索引。</li>
</ul>
<h2>帮助中心（help）</h2>
<ol class="doc-steps">
<li>「帮助分类」：创建分类（标题、URL、图标、排序），决定前台帮助页的栏目结构。</li>
<li>「帮助文章」：撰写文章（标题、分类、描述、排序、发布状态）。正文支持 HTML：<code>h2/h3</code> 自动生成「本页目录」，<code>div.doc-note doc-note--info/warn/tip</code> 为提示条，<code>details.doc-faq</code> 为折叠 FAQ。</li>
<li>前台 <code>/help</code> 即时生效；文章内容以官方种子为基线，可在后台继续增改。</li>
</ol>
<h2>语言包（languages）</h2>
<ul>
<li>后台可维护界面语言与翻译键值；帮助文章等 DB 内容为独立数据，不随界面语言切换。</li>
</ul>
<div class="doc-note doc-note--tip"><p><strong>排序规则：</strong>分类与文章均按 order 升序展示，数值小的在前。</p></div>
HTML,
                    ],
                    [
                        'url' => 'admin-operations',
                        'title' => '系统运维：Cron、日志、缓存、插件与许可证',
                        'desc' => '计划任务心跳、日志排查、缓存清理、插件管理与许可证上传刷新。',
                        'content' => <<<'HTML'
<h2>计划任务（cron）</h2>
<p>关键词排名刷新、外链验证、数据保留清理、套餐到期处理都依赖 cron。在服务器配置每分钟调度：</p>
<pre><code>* * * * * cd /path/to/monit &amp;&amp; php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
<p>「系统设置 → Cron」页面查看各任务最近执行心跳；长时间无心跳说明调度未生效。</p>
<h2>日志与排障</h2>
<ul>
<li>「管理后台 → 日志」在线查看应用日志，支持下载；<code>storage/logs/laravel.log</code> 为底层文件。</li>
<li>功能「静默失效」（如热图/回放无数据）时优先查日志中的异常与设置读取错误。</li>
</ul>
<h2>缓存</h2>
<ul>
<li>「系统设置 → 缓存」提供一键清理（应用缓存、配置缓存、路由缓存、视图缓存）。</li>
<li>修改 <code>.env</code> 或配置后建议清理并重建缓存。</li>
</ul>
<h2>插件与许可证</h2>
<ul>
<li><strong>插件</strong>：安装/卸载/启停扩展模块，个别插件带独立设置页。</li>
<li><strong>许可证</strong>：查看当前授权状态，支持上传与刷新许可证文件。</li>
</ul>
<h2>备份建议</h2>
<ul>
<li>定期备份数据库与 <code>storage/app</code>（含 GeoIP 库、上传文件）。</li>
<li>升级版本前务必备份，并阅读版本说明中的部署清单（migration / seed 命令）。</li>
</ul>
HTML,
                    ],
                ],
            ],
        ];
    }
}
