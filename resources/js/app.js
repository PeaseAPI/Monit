//

/* ── SEO URL 自动补全协议 ──
   用户输入 example.com 时自动补上 https://，
   无需手动输入 http:// 或 https://
*/
document.addEventListener('submit', function (e) {
    const form = e.target;
    if (!form.matches('[data-seo-form]')) return;

    const input = form.querySelector('input[name="url"]');
    if (!input) return;

    let url = input.value.trim();
    if (url && !/^https?:\/\//i.test(url)) {
        input.value = 'https://' + url;
    }
});

