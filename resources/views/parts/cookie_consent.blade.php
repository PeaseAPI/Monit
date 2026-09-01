{{-- Cookie 同意横幅（规格书 §6.1：/cookie-consent GDPR）
     消费设置（cookie_consent 组，对标原版键名）：
     - cookie_consent_is_enabled 总开关
     - cookie_consent_type：read（仅告知，无拒绝钮）/ opt-in（先拒后允）
     - cookie_consent_message：正文覆盖（优先于 title/description 旧键）
     - cookie_consent_position_y：top / bottom（默认 bottom）
     - cookie_consent_position_x：left / center（默认 center） --}}
@php
    $ccEnabled = \App\Support\Settings::get('cookie_consent.cookie_consent_is_enabled');
    $ccOn = in_array($ccEnabled, [true, 1, '1', 'true'], true);
    $ccType = (string) \App\Support\Settings::get('cookie_consent.cookie_consent_type', 'read');
    $ccPosY = \App\Support\Settings::get('cookie_consent.cookie_consent_position_y') === 'top' ? 'top-0 border-b' : 'bottom-0 border-t';
    $ccPosX = \App\Support\Settings::get('cookie_consent.cookie_consent_position_x') === 'left' ? 'items-start text-left' : 'items-center sm:justify-between';
@endphp
@if ($ccOn)
<div id="monit-cookie-banner" hidden class="fixed inset-x-0 {{ $ccPosY }} z-[60] border-zinc-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-5xl flex-col gap-3 px-6 py-4 sm:flex-row {{ $ccPosX }}">
        <div>
            <p class="font-semibold text-zinc-900">{{ \App\Support\Settings::get('cookie_consent.cookie_consent_title', '我们使用 Cookie') }}</p>
            <p class="mt-1 text-sm text-zinc-500">{{ \App\Support\Settings::get('cookie_consent.cookie_consent_message') ?: \App\Support\Settings::get('cookie_consent.cookie_consent_description', '本网站使用 Cookie 来提升您的浏览体验。') }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            @if ($ccType === 'opt-in')
                <button type="button" id="monit-cc-reject" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">{{ __('cookie.reject') }}</button>
            @endif
            <button type="button" id="monit-cc-accept" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">{{ \App\Support\Settings::get('cookie_consent.cookie_consent_button_text', '接受') }}</button>
        </div>
    </div>
</div>
<script>
(function () {
    var KEY = 'monit_cookie_consent';
    try { if (localStorage.getItem(KEY)) { return; } } catch (e) { return; }

    var banner = document.getElementById('monit-cookie-banner');
    if (!banner) { return; }
    banner.hidden = false;

    function decide(consent) {
        try { localStorage.setItem(KEY, consent); } catch (e) {}
        banner.hidden = true;

        var token = document.querySelector('meta[name="csrf-token"]');
        fetch("{{ route('cookie.consent') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token ? token.content : '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ consent: consent === 'accepted' ? 'accepted' : 'rejected' }),
        }).catch(function () {});
    }

    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'monit-cc-accept') { decide('accepted'); }
        if (e.target && e.target.id === 'monit-cc-reject') { decide('rejected'); }
    });
})();
</script>
@endif
