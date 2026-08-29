<?php

/**
 * Email Shield 启动入口（规格书 §14.5 结构）
 * 注册全局 helper 与 Blade 指令 @emailShield($email)
 */

use App\Support\PluginManager;

if (! function_exists('email_shield')) {
    /**
     * 输出 JS 反混淆的邮箱（爬虫无法直接抓取）
     */
    function email_shield(string $email): string
    {
        if (! PluginManager::setting('email-shield', 'is_enabled', true)) {
            return e($email);
        }

        $method = PluginManager::setting('email-shield', 'method', 'rot13');

        if ($method === 'entity') {
            $encoded = ''; // HTML 实体编码
            foreach (str_split($email) as $char) {
                $encoded .= '&#' . ord($char) . ';';
            }

            return '<a href="mailto:' . $encoded . '">' . $encoded . '</a>';
        }

        // rot13 + JS 反解
        $rot13 = str_rot13($email);

        return '<span class="email-shield" data-email="' . e($rot13) . '">'
            . '<script>document.currentScript.replaceWith((m=>`<a href="mailto:${m}">${m}</a>`)(document.currentScript.parentElement.dataset.email.replace(/[a-zA-Z]/g,c=>String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26))))</script>'
            . '</span>';
    }
}

// Blade 指令：@emailShield($user->email)
\Illuminate\Support\Facades\Blade::directive('emailShield', function ($expression) {
    return "<?php echo email_shield({$expression}); ?>";
});
