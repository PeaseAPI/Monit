<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monit · 安装向导</title>
    <style>
        body{font-family:system-ui,-apple-system,'PingFang SC',sans-serif;background:#f4f4f5;margin:0;padding:40px 16px;color:#18181b}
        .card{max-width:560px;margin:0 auto;background:#fff;border:1px solid #e4e4e7;border-radius:16px;padding:32px}
        h1{font-size:22px;margin:0 0 4px}
        .sub{color:#71717a;font-size:14px;margin-bottom:24px}
        .step{display:flex;gap:8px;margin-bottom:24px}
        .step span{flex:1;height:4px;border-radius:2px;background:#e4e4e7}
        .step span.on{background:#4f46e5}
        label{display:block;font-size:13px;font-weight:600;margin:14px 0 6px;color:#3f3f46}
        input,select{width:100%;box-sizing:border-box;padding:9px 12px;border:1px solid #d4d4d8;border-radius:8px;font-size:14px}
        button{margin-top:24px;width:100%;padding:11px;background:#4f46e5;color:#fff;border:0;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer}
        button:disabled{background:#a5b4fc;cursor:not-allowed}
        .check{display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid #f4f4f5;font-size:14px}
        .ok{color:#059669}.bad{color:#dc2626}
        .error{background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;padding:10px 12px;border-radius:8px;font-size:13px;margin-top:12px}
    </style>
</head>
<body>
<div class="card">
    @php $current = $step === 'requirements' ? 1 : 3; @endphp
    <div class="step">
        <span class="{{ $current >= 1 ? 'on' : '' }}"></span>
        <span class="{{ $current >= 2 ? 'on' : '' }}"></span>
        <span class="{{ $current >= 3 ? 'on' : '' }}"></span>
    </div>

    @if ($step === 'requirements')
        <h1>环境检查</h1>
        <p class="sub">第 1 步 · 共 3 步：确认服务器满足运行要求</p>
        @foreach ($checks as $label => $passed)
            <div class="check">
                <span>{{ $label }}</span>
                <span class="{{ $passed ? 'ok' : 'bad' }}">{{ $passed ? '✓ 通过' : '✗ 未通过' }}</span>
            </div>
        @endforeach
        @if ($allPassed)
            <form method="POST" action="{{ route('install.database') }}">
                @csrf
                <label>数据库类型</label>
                <select name="connection">
                    <option value="sqlite">SQLite（推荐 · 零依赖）</option>
                    <option value="mysql">MySQL 8.0+</option>
                </select>
                <div id="mysql-fields" style="display:none">
                    <label>主机</label><input name="host" value="127.0.0.1">
                    <label>端口</label><input name="port" value="3306">
                    <label>数据库名</label><input name="database" value="monit">
                    <label>用户名</label><input name="username" value="monit">
                    <label>密码</label><input name="password" type="password">
                </div>
                <button type="submit">执行迁移并继续</button>
            </form>
            <script>document.querySelector('select[name=connection]').addEventListener('change',function(){document.getElementById('mysql-fields').style.display=this.value==='mysql'?'block':'none'});</script>
        @else
            <button disabled>请先满足以上要求</button>
        @endif
    @elseif ($step === 'admin')
        <h1>创建管理员</h1>
        <p class="sub">第 3 步 · 共 3 步：数据库已就绪，创建超级管理员账户</p>
        <form method="POST" action="{{ route('install.admin') }}">
            @csrf
            <label>管理员名称</label>
            <input name="name" value="{{ old('name', 'admin') }}" required>
            <label>邮箱</label>
            <input name="email" type="email" value="{{ old('email') }}" required>
            <label>密码（≥8 位）</label>
            <input name="password" type="password" required>
            <label>确认密码</label>
            <input name="password_confirmation" type="password" required>
            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif
            <button type="submit">完成安装</button>
        </form>
    @endif
</div>
</body>
</html>
