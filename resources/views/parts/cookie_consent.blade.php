{{-- Cookie 同意横幅（规格书 §6.1：/cookie-consent GDPR） --}}
@php
    $ccEnabled = \App\Support\Settings::get('cookie_consent.cookie_consent_is_enabled');
    $ccOn = in_array($ccEnabled, [true, 1, '1', 'true'], true);
@endphp
@if ($ccOn)
<div id="monit-cookie-banner" hidden class="fixed inset-x-0 bottom-0 z-[60] border-t border-zinc-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-5xl flex-col items-start gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="font-semibold text-zinc-900">{{ \App\Support\Settings::get('cookie_consent.cookie_consent_title', '我们使用 Cookie') }}</p>
            <p class="mt-1 text-sm text-zinc-500">{{ \App\Support\Settings::get('cookie_consent.cookie_consent_description', '本网站使用 Cookie 来提升您的浏览体验。') }}</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <button type="button" id="monit-cc-reject" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm text-zinc-600 hover:bg-zinc-50">{{ __('cookie.reject') }}</button>
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
