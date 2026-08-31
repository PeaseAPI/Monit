<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monit · 安装向导</title>
    <style>
        :root{--primary:#4f46e5;--primary-dark:#4338ca;--primary-light:#eef2ff;--green:#059669;--green-bg:#ecfdf5;--red:#dc2626;--red-bg:#fef2f2;--amber:#d97706;--amber-bg:#fffbeb;--ink:#18181b;--ink-2:#52525b;--ink-3:#a1a1aa;--line:#e4e4e7;--bg:#f4f4f5}
        *{box-sizing:border-box}
        body{font-family:system-ui,-apple-system,'PingFang SC','Microsoft YaHei',sans-serif;background:var(--bg);margin:0;padding:32px 16px 48px;color:var(--ink);min-height:100vh}
        .wrap{max-width:760px;margin:0 auto}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}
        .brand .logo{width:42px;height:42px;border-radius:11px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:800;font-size:20px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(99,102,241,.35)}
        .brand h1{font-size:19px;margin:0}
        .brand .ver{font-size:12px;color:var(--ink-3)}
        .steps{display:flex;justify-content:space-between;margin:0 4px 26px;position:relative}
        .steps::before{content:'';position:absolute;top:15px;left:calc(10% + 14px);right:calc(10% + 14px);height:2px;background:var(--line);z-index:0}
        .step-item{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:6px;width:20%}
        .step-item .dot{width:32px;height:32px;border-radius:50%;background:#fff;border:2px solid var(--line);color:var(--ink-3);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;transition:all .2s}
        .step-item .lbl{font-size:12px;color:var(--ink-3);white-space:nowrap}
        .step-item.done .dot{background:var(--green);border-color:var(--green);color:#fff}
        .step-item.done .lbl{color:var(--green)}
        .step-item.now .dot{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 0 0 5px var(--primary-light)}
        .step-item.now .lbl{color:var(--primary);font-weight:600}
        .card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:30px 32px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
        h2{font-size:20px;margin:0 0 4px}
        .sub{color:var(--ink-2);font-size:13.5px;margin:0 0 22px}
        .check{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #f4f4f5}
        .check:last-child{border-bottom:0}
        .check .name{flex:1;font-size:14px;font-weight:500}
        .check .name small{display:block;font-weight:400;color:var(--ink-3);font-size:12px;margin-top:3px;font-family:ui-monospace,Menlo,monospace;word-break:break-all}
        .check .cur{font-size:12px;color:var(--ink-2);background:#f4f4f5;border-radius:6px;padding:3px 8px;white-space:nowrap}
        .badge{font-size:12.5px;font-weight:600;border-radius:6px;padding:3px 10px;white-space:nowrap}
        .badge.ok{color:var(--green);background:var(--green-bg)}
        .badge.bad{color:var(--red);background:var(--red-bg)}
        .badge.warn{color:var(--amber);background:var(--amber-bg)}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:0 16px}
        @media(max-width:560px){.grid{grid-template-columns:1fr}}
        label{display:block;font-size:13px;font-weight:600;margin:15px 0 6px;color:#3f3f46}
        input{width:100%;padding:9px 12px;border:1px solid #d4d4d8;border-radius:8px;font-size:14px;outline:none;transition:border .15s,box-shadow .15s}
        input:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light)}
        .btn{margin-top:26px;width:100%;padding:12px;background:var(--primary);color:#fff;border:0;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:background .15s}
        .btn:hover{background:var(--primary-dark)}
        .btn:disabled{background:#c7d2fe;cursor:not-allowed}
        .btn-ghost{display:inline-block;margin-top:14px;padding:10px 22px;background:#fff;color:var(--ink-2);border:1px solid #d4d4d8;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;text-decoration:none;transition:all .15s}
        .btn-ghost:hover{border-color:var(--primary);color:var(--primary)}
        .btn-row{display:flex;gap:10px}
        .btn-row .btn,.btn-row .btn-ghost{flex:1;margin-top:26px}
        .btn-test{padding:10px 16px;background:var(--primary-light);color:var(--primary);border:1px solid #c7d2fe;border-radius:8px;font-size:13.5px;font-weight:600;cursor:pointer;white-space:nowrap;margin-top:0;transition:all .15s}
        .btn-test:hover{background:#e0e7ff}
        .btn-test:disabled{opacity:.6;cursor:not-allowed}
        .test-bar{display:flex;gap:10px;align-items:center;margin-top:16px}
        .test-result{margin-top:14px;padding:10px 14px;border-radius:8px;font-size:13px;line-height:1.6;display:none;word-break:break-all}
        .test-result.ok{display:block;background:var(--green-bg);color:var(--green);border:1px solid #a7f3d0}
        .test-result.bad{display:block;background:var(--red-bg);color:var(--red);border:1px solid #fecaca}
        .error{background:var(--red-bg);border:1px solid #fecaca;color:#b91c1c;padding:12px 14px;border-radius:10px;font-size:13.5px;margin-bottom:20px;line-height:1.7;word-break:break-all}
        .note{background:var(--primary-light);border:1px solid #c7d2fe;color:#3730a3;border-radius:10px;padding:12px 14px;font-size:13px;line-height:1.8;margin-top:20px;text-align:left}
        .note code{font-family:ui-monospace,Menlo,monospace;background:rgba(255,255,255,.7);padding:1px 6px;border-radius:4px;font-size:12px}
        .summary{border:1px solid var(--line);border-radius:12px;overflow:hidden;margin:6px 0 4px;text-align:left}
        .summary .row{display:flex;justify-content:space-between;gap:16px;padding:11px 16px;font-size:14px;border-bottom:1px solid #f4f4f5}
        .summary .row:last-child{border-bottom:0}
        .summary .row .k{color:var(--ink-2)}
        .summary .row .v{font-weight:600;word-break:break-all;text-align:right}
        .done-icon{width:72px;height:72px;border-radius:50%;background:var(--green-bg);border:2px solid #a7f3d0;display:flex;align-items:center;justify-content:center;margin:6px auto 18px}
        .done-icon svg{width:36px;height:36px;stroke:var(--green);stroke-width:3;fill:none;stroke-linecap:round;stroke-linejoin:round}
        .footer{text-align:center;color:var(--ink-3);font-size:12px;margin-top:24px}
        .spinner{display:inline-block;width:15px;height:15px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;vertical-align:-3px;margin-right:7px}
        @keyframes spin{to{transform:rotate(360deg)}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">
        <div class="logo">M</div>
        <div>
            <h1>Monit 安装向导</h1>
            <div class="ver">开源网站分析系统 · MySQL 版</div>
        </div>
    </div>

    @php
        $stepIndex = match ($step) {
            'requirements' => 1, 'permissions' => 2, 'database' => 3, 'admin' => 4, 'finish' => 5,
        };
        $stepLabels = ['环境检测', '目录权限', '数据库配置', '站点配置', '安装完成'];
    @endphp
    <div class="steps">
        @foreach ($stepLabels as $i => $label)
            @php $n = $i + 1; @endphp
            <div class="step-item {{ $n < $stepIndex ? 'done' : ($n === $stepIndex ? 'now' : '') }}">
                <div class="dot">{{ $n < $stepIndex ? '✓' : $n }}</div>
                <div class="lbl">{{ $label }}</div>
            </div>
        @endforeach
    </div>

    <div class="card">
        {{-- 无 Session 环境（无中间件路由组）：错误/回填用显式变量，不用 @csrf / old() / $errors --}}
        @if (! empty($errors))
            <div class="error">
                @foreach ($errors as $error)
                    {{ $error }}<br>
                @endforeach
            </div>
        @endif

        @if ($step === 'requirements')
        @php
            $failedRequired = collect($checks ?? [])->where('required', true)->where('passed', false)->count();
            $warnCount = collect($checks ?? [])->where('required', false)->where('passed', false)->count();
        @endphp
        <h2>环境检测</h2>
        <p class="sub">检测服务器是否满足 Monit 运行要求（必需项全部通过才能继续）</p>
        @foreach ($checks as $check)
            <div class="check">
                <div class="name">
                    {{ $check['label'] }}
                    @if (! $check['passed'])
                        <small>{{ $check['hint'] }}</small>
                    @endif
                </div>
                <span class="cur">{{ $check['current'] }}</span>
                @if ($check['passed'])
                    <span class="badge ok">✓ 通过</span>
                @elseif ($check['required'])
                    <span class="badge bad">✗ 未通过</span>
                @else
                    <span class="badge warn">⚠ 建议</span>
                @endif
            </div>
        @endforeach
        <form method="POST" action="{{ route('install.requirements.submit') }}">
            @if ($failedRequired === 0)
                <button type="submit" class="btn">{{ $warnCount > 0 ? '继续安装（含 '.$warnCount.' 项建议项未开启）' : '下一步 · 目录权限' }}</button>
            @else
                <button type="submit" class="btn">重新检测</button>
            @endif
        </form>
    @elseif ($step === 'permissions')
        @php $failedPerm = collect($checks ?? [])->where('passed', false)->count(); @endphp
        <h2>目录权限检测</h2>
        <p class="sub">Monit 需要写入缓存、日志与配置文件，请确保以下目录/文件可写</p>
        @foreach ($checks as $check)
            <div class="check">
                <div class="name">
                    {{ $check['label'] }}
                    @if (! $check['passed'])
                        <small>{{ $check['hint'] }}</small>
                    @endif
                </div>
                <span class="cur">{{ $check['current'] }}</span>
                @if ($check['passed'])
                    <span class="badge ok">✓ 可写</span>
                @else
                    <span class="badge bad">✗ 异常</span>
                @endif
            </div>
        @endforeach
        <form method="POST" action="{{ route('install.permissions.submit') }}">
            @if ($failedPerm === 0)
                <button type="submit" class="btn">下一步 · 数据库配置</button>
            @else
                <button type="submit" class="btn">重新检测</button>
            @endif
        </form>

@elseif ($step === 'database')
        <h2>数据库配置</h2>
        <p class="sub">Monit 使用 MySQL 存储数据（推荐 5.7+ / 8.0），数据库不存在时将自动创建</p>
        <form method="POST" action="{{ route('install.database.submit') }}" id="db-form">
            <div class="grid">
                <div>
                    <label>数据库主机</label>
                    <input name="host" value="{{ $old['host'] ?? '127.0.0.1' }}" placeholder="127.0.0.1" required>
                </div>
                <div>
                    <label>端口</label>
                    <input name="port" value="{{ $old['port'] ?? '3306' }}" placeholder="3306" required>
                </div>
            </div>
            <label>数据库名</label>
            <input name="database" value="{{ $old['database'] ?? 'monit' }}" placeholder="monit" pattern="[A-Za-z0-9_\-]+" required>
            <div class="grid">
                <div>
                    <label>用户名</label>
                    <input name="username" value="{{ $old['username'] ?? 'root' }}" placeholder="root" required>
                </div>
                <div>
                    <label>密码</label>
                    <input name="password" type="password" placeholder="无密码可留空">
                </div>
            </div>
            <div class="test-bar">
                <button type="button" class="btn-test" id="btn-test">测试连接</button>
                <span class="sub" style="margin:0">先验证连通性，再执行安装迁移</span>
            </div>
            <div class="test-result" id="test-result"></div>
            <button type="submit" class="btn" id="btn-db-submit">安装数据表并继续</button>
        </form>
    @elseif ($step === 'admin')
        <h2>站点与管理员配置</h2>
        <p class="sub">数据表已就绪，设置站点信息并创建超级管理员账户（安装完成后不可再访问向导）</p>
        <form method="POST" action="{{ route('install.admin.submit') }}" id="admin-form">
            <label>网站名称</label>
            <input name="site_name" value="{{ $old['site_name'] ?? ($defaultName ?? 'Monit 网站分析') }}" required>
            <label>网站地址</label>
            <input name="site_url" type="url" value="{{ $old['site_url'] ?? ($defaultUrl ?? '') }}" placeholder="https://example.com" required>
            <label>管理员名称</label>
            <input name="name" value="{{ $old['name'] ?? 'admin' }}" required>
            <label>管理员邮箱</label>
            <input name="email" type="email" value="{{ $old['email'] ?? '' }}" required>
            <div class="grid">
                <div>
                    <label>密码（≥ 8 位）</label>
                    <input name="password" type="password" required>
                </div>
                <div>
                    <label>确认密码</label>
                    <input name="password_confirmation" type="password" required>
                </div>
            </div>
            <button type="submit" class="btn" id="btn-admin-submit">完成安装</button>
        </form>

@elseif ($step === 'finish')
        <div class="done-icon">
            <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h2 style="text-align:center">安装完成！</h2>
        <p class="sub" style="text-align:center">Monit 已成功安装，可以开始使用了</p>
        <div class="summary">
            <div class="row"><span class="k">网站名称</span><span class="v">{{ $siteName }}</span></div>
            <div class="row"><span class="k">网站地址</span><span class="v">{{ $siteUrl }}</span></div>
            <div class="row"><span class="k">管理员邮箱</span><span class="v">{{ $adminEmail }}</span></div>
            <div class="row"><span class="k">数据库</span><span class="v">MySQL · {{ $dbDatabase }}</span></div>
        </div>
        <div class="btn-row">
            <a class="btn-ghost" href="{{ url('/login') }}">前往登录</a>
            <a class="btn" href="{{ url('/') }}" style="text-align:center;text-decoration:none">打开首页</a>
        </div>
        <div class="note">
            <strong>后续建议：</strong><br>
            1. 系统已写入安装锁（<code>storage/installed.lock</code>），安装向导自动失效，无需删除任何文件；<br>
            2. 请配置计划任务以保证统计汇总与数据归档按时执行（宝塔 → 计划任务，每 5 分钟）：<br>
            <code>* * * * * cd {{ base_path() }} && php artisan schedule:run &gt;&gt; /dev/null 2&gt;&amp;1</code><br>
            3. 线上环境建议将 <code>public/</code> 设为站点根目录，并通过 HTTPS 访问。
        </div>
    @endif
    </div>

    <div class="footer">© {{ date('Y') }} Monit · 自托管网站分析系统</div>
</div>
<script>
    (function () {
        // 数据库连接测试（AJAX，不写 .env 不迁移）
        var btnTest = document.getElementById('btn-test');
        if (btnTest) {
            btnTest.addEventListener('click', function () {
                var form = document.getElementById('db-form');
                var result = document.getElementById('test-result');
                var data = new FormData(form);
                btnTest.disabled = true;
                btnTest.textContent = '正在测试…';
                result.className = 'test-result';
                result.textContent = '';
                fetch("{{ route('install.test-db') }}", { method: 'POST', body: data })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        result.className = 'test-result ' + (json.ok ? 'ok' : 'bad');
                        result.textContent = json.message;
                    })
                    .catch(function (e) {
                        result.className = 'test-result bad';
                        result.textContent = '请求失败：' + e;
                    })
                    .finally(function () {
                        btnTest.disabled = false;
                        btnTest.textContent = '测试连接';
                    });
            });
        }

        // 迁移/写入数据耗时较长：提交时按钮转 loading 防重复点击
        function bindLoading(formId, btnId, text) {
            var form = document.getElementById(formId);
            var btn = document.getElementById(btnId);
            if (! form || ! btn) return;
            form.addEventListener('submit', function () {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span>' + text;
            });
        }
        bindLoading('db-form', 'btn-db-submit', '正在创建数据表，请稍候…');
        bindLoading('admin-form', 'btn-admin-submit', '正在完成安装…');
    })();
</script>
</body>
</html>
