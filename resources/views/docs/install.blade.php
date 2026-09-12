@extends('layouts.public')
@section('title', '安装指南')
@section('main_class', 'mx-auto w-full max-w-4xl px-6 py-12')

{{-- 文档中心 · 安装指南（M27 自静态 public/docs/install.html 迁移，统一全站头尾布局） --}}
@section('content')
<div class="text-center">
    <h1 class="text-4xl font-bold tracking-tight text-zinc-900">安装指南</h1>
    <p class="mt-3 text-zinc-600">从零到拥有自己的网站分析平台,最快 5 分钟。</p>
</div>
<div class="mt-8 flex flex-wrap justify-center gap-3 text-sm">
    <a href="#req" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">环境要求</a>
    <a href="#docker" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">Docker 部署(推荐)</a>
    <a href="#manual" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">手动部署</a>
    <a href="#webserver" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">Nginx / Apache</a>
    <a href="#cron" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">Cron 与队列</a>
    <a href="#ssl" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">HTTPS</a>
    <a href="#faq" class="rounded-full border border-zinc-300 px-4 py-1.5 text-zinc-600 transition hover:border-brand-500 hover:text-brand-600">常见问题</a>
</div>

<section id="req" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">1 · 环境要求</h2>
    <p class="mt-2 text-sm text-zinc-600">Monit 基于 Laravel 12 构建,以下为生产环境最低/推荐配置。</p>
    <div class="mt-5 overflow-x-auto rounded-2xl border border-zinc-200 bg-white">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-zinc-700"><tr><th class="px-5 py-3 font-semibold">组件</th><th class="px-5 py-3 font-semibold">最低</th><th class="px-5 py-3 font-semibold">推荐</th></tr></thead>
            <tbody class="divide-y divide-zinc-100 text-zinc-600">
                <tr><td class="px-5 py-3">PHP</td><td class="px-5 py-3">8.3</td><td class="px-5 py-3">8.3+（fpm）</td></tr>
                <tr><td class="px-5 py-3">数据库</td><td class="px-5 py-3">MySQL 8 / MariaDB 10.6 / PostgreSQL 15</td><td class="px-5 py-3">MySQL 8</td></tr>
                <tr><td class="px-5 py-3">Web 服务器</td><td class="px-5 py-3">Nginx 1.24 / Apache 2.4</td><td class="px-5 py-3">Nginx（含 HTTP/2）</td></tr>
                <tr><td class="px-5 py-3">PHP 扩展</td><td class="px-5 py-3" colspan="2">ctype, curl, dom, fileinfo, json, mbstring, openssl, pcre, pdo, tokenizer, xml, gd, zip, bcmath, intl</td></tr>
                <tr><td class="px-5 py-3">Composer</td><td class="px-5 py-3" colspan="2">2.x</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section id="docker" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">2 · Docker 部署（推荐）</h2>
    <p class="mt-2 text-sm text-zinc-600">项目自带 docker-compose,一条命令拉起应用 + 数据库 + Nginx。</p>
    <pre class="mt-5 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code># 1. 克隆仓库
git clone https://github.com/your-org/monit.git && cd monit

# 2. 准备环境变量
cp .env.example .env

# 3. 启动（应用 + MySQL + Nginx）
docker compose up -d

# 4. 初始化（依赖、密钥、建表、初始数据）
docker compose exec app composer install --no-dev
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 5. 前端资源（镜像已预构建则可跳过）
docker compose exec app npm ci && npm run build</code></pre>
    <p class="mt-4 text-sm text-zinc-600">访问 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">http://服务器IP</code> 即进入安装向导/登录页。默认管理员账号在 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">.env</code> 的 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">ADMIN_EMAIL / ADMIN_PASSWORD</code> 中设置。</p>
</section>

<section id="manual" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">3 · 手动部署（LNMP）</h2>
    <h3 class="mt-5 font-semibold text-zinc-800">3.1 获取代码与依赖</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>git clone https://github.com/your-org/monit.git /var/www/monit
cd /var/www/monit
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">3.2 配置 .env</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>APP_URL=https://your-domain.com
APP_LOCALE=zh_CN

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=monit
DB_USERNAME=monit
DB_PASSWORD=强密码

# 邮件（用于注册验证 / 报告 / 通知）
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@example.com
MAIL_PASSWORD=***
MAIL_ENCRYPTION=ssl</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">3.3 初始化</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>php artisan migrate --seed      # 建表 + 内置套餐/设置
php artisan storage:link         # 公开磁盘软链
npm ci && npm run build          # 构建前端资源

# 生产缓存优化
php artisan config:cache
php artisan route:cache
php artisan view:cache</code></pre>
    <div class="mt-4 rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">也可以直接访问 <code class="rounded bg-white/60 px-1.5 py-0.5 text-xs">https://your-domain.com/install</code> 使用网页安装向导,可视化完成数据库与初始管理员配置。</div>
</section>
<section id="webserver" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">4 · Nginx / Apache 伪静态</h2>
    <p class="mt-2 text-sm text-zinc-600">规则已内置于仓库 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">deploy/</code> 目录,无需自行编写。</p>
    <h3 class="mt-5 font-semibold text-zinc-800">Nginx</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>sudo cp deploy/nginx/monit.conf /etc/nginx/sites-available/monit.conf
sudo ln -s /etc/nginx/sites-available/monit.conf /etc/nginx/sites-enabled/
# 编辑 server_name 与 root（指向 public/）后
sudo nginx -t && sudo systemctl reload nginx</code></pre>
    <p class="mt-4 text-sm text-zinc-600">核心伪静态规则（已包含在上述配置中）：</p>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>root /var/www/monit/public;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">Apache</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>sudo a2enmod rewrite headers expires
sudo cp deploy/apache/monit.conf /etc/apache2/sites-available/monit.conf
sudo a2ensite monit && sudo systemctl reload apache2</code></pre>
    <p class="mt-4 text-sm text-zinc-600">Apache 伪静态由项目自带 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">public/.htaccess</code> 处理,只需确保虚拟主机中 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">AllowOverride All</code>。</p>
</section>

<section id="cron" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">5 · Cron 定时任务与队列</h2>
    <h3 class="mt-5 font-semibold text-zinc-800">调度器（必须）</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code># crontab -e 添加：
* * * * * cd /var/www/monit && php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code></pre>
    <p class="mt-4 text-sm text-zinc-600">调度器驱动 15 个内置任务:数据保留清理、邮件报告、配额提醒、会话聚合等。可用 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">php artisan schedule:list</code> 查看全部任务。</p>
    <h3 class="mt-5 font-semibold text-zinc-800">队列 Worker（启用队列时）</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>php artisan queue:work --tries=3 --max-time=3600
# 生产建议配合 Supervisor 守护</code></pre>
</section>

<section id="ssl" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">6 · 启用 HTTPS</h2>
    <pre class="mt-4 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com</code></pre>
    <p class="mt-4 text-sm text-zinc-600">证书自动续期已由 certbot 定时任务处理。像素采集端点默认支持跨域 HTTPS 上报,无需额外配置。</p>
</section>

<section id="faq" class="mt-14">
    <h2 class="text-2xl font-bold text-zinc-900">7 · 常见问题</h2>
    <h3 class="mt-5 font-semibold text-zinc-800">Q：安装后页面 500 / 白屏？</h3>
    <p class="mt-2 text-sm text-zinc-600">检查 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">storage/</code> 与 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">bootstrap/cache/</code> 目录权限（web 用户可写）：<code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">chown -R www-data:www-data storage bootstrap/cache</code>。</p>
    <h3 class="mt-5 font-semibold text-zinc-800">Q：像素已嵌入但没有数据？</h3>
    <ol class="mt-2 list-decimal space-y-1 pl-6 text-sm text-zinc-600">
        <li>确认网站"已启用"且域名 host 与嵌入页面一致;</li>
        <li>浏览器控制台查看 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">/pixel-track/</code> 请求是否 204;</li>
        <li>若开启了 IP 排除 / 爬虫过滤,核对自身是否被过滤。</li>
    </ol>
    <h3 class="mt-5 font-semibold text-zinc-800">Q：忘记管理员密码？</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>php artisan tinker
&gt; \App\Models\User::where('type','admin')-&gt;first()-&gt;update(['password' =&gt; bcrypt('新密码')]);</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">Q：如何升级版本？</h3>
    <pre class="mt-3 overflow-x-auto rounded-2xl bg-zinc-950 p-5 text-xs leading-relaxed text-zinc-100"><code>git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache &amp;&amp; php artisan route:cache &amp;&amp; php artisan view:cache</code></pre>
    <h3 class="mt-5 font-semibold text-zinc-800">Q：为什么必须备份 APP_KEY？丢失会怎样？</h3>
    <p class="mt-2 text-sm text-zinc-600"><code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">APP_KEY</code>（.env）是全站加密基座：<strong>用户 API Key 的加密数据由它解密</strong>（API Key 明文不落库）。若丢失且无备份，所有用户 API Key 将无法验证（API 调用一律 401），需各用户在账号页重新生成。因此：</p>
    <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-zinc-600">
        <li>把 <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">APP_KEY</code> 记入密码管理器或离线备份（<strong>不要提交进 git</strong>）；</li>
        <li><code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">.env</code>（含 APP_KEY）与数据库的访问权限同级对待——两者都拿到即可冒用任意用户 API；只拿到数据库则无法还原任何 API Key；</li>
        <li>升级版本（git pull + migrate）时 API Key 存储形态自动平滑转换，既有集成不失效；</li>
        <li><strong>已部署站点严禁随手重跑</strong> <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs">php artisan key:generate</code>（会生成新 key，旧加密数据全部不可解）。</li>
    </ul>
</section>

<div class="mt-16 border-t border-zinc-200 pt-8">
    <div class="flex flex-wrap justify-center gap-6 text-sm">
        <a href="{{ route('docs.index') }}" class="text-brand-600 hover:underline">产品介绍</a>
        <a href="{{ route('docs.usage') }}" class="text-brand-600 hover:underline">使用手册</a>
        <a href="{{ route('api.docs') }}" class="text-brand-600 hover:underline">API 文档</a>
        <a href="{{ route('help') }}" class="text-brand-600 hover:underline">帮助中心</a>
    </div>
</div>
@endsection
