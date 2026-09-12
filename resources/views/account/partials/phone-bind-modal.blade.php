{{-- M30：绑定/换绑手机号弹窗（fetch 发码 60s 倒计时 + 验证码绑定，全 AJAX JSON） --}}
<div id="phone-bind-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-zinc-900/50 p-4" role="dialog" aria-modal="true">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-zinc-900">{{ __('account.phone_bind_btn') }}</h3>
            <button type="button" onclick="closePhoneBindModal()" class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600" aria-label="close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="mt-1 text-xs text-zinc-500">{{ __('account.phone_modal_hint') }}</p>

        <div class="mt-4 space-y-3">
            <div>
                <label class="form-label" for="phone-modal-number">{{ __('auth.phone') }}</label>
                <div class="flex gap-2">
                    <input type="tel" id="phone-modal-number" maxlength="11" inputmode="numeric"
                           placeholder="{{ __('auth.phone_placeholder') }}"
                           class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
                    <button type="button" id="phone-send-code-btn" onclick="sendPhoneBindCode()"
                            class="whitespace-nowrap rounded-xl border border-brand-600 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-50">
                        {{ __('auth.get_sms_code') }}
                    </button>
                </div>
            </div>
            <div>
                <label class="form-label" for="phone-modal-code">{{ __('auth.sms_code') }}</label>
                <input type="text" id="phone-modal-code" inputmode="numeric" maxlength="6"
                       placeholder="{{ __('auth.sms_code_placeholder') }}"
                       class="w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-sm">
            </div>
            <p id="phone-modal-error" class="hidden rounded-xl bg-red-50 px-4 py-2.5 text-sm text-red-600"></p>
            <p id="phone-modal-info" class="hidden rounded-xl bg-emerald-50 px-4 py-2.5 text-sm text-emerald-600"></p>
        </div>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" onclick="closePhoneBindModal()" class="rounded-xl border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50">
                {{ __('common.cancel') }}
            </button>
            <button type="button" id="phone-bind-submit-btn" onclick="submitPhoneBind()" class="rounded-xl bg-brand-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                {{ __('account.phone_bind_btn') }}
            </button>
        </div>
    </div>
</div>

<script>
function openPhoneBindModal() {
    const modal = document.getElementById('phone-bind-modal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('phone-modal-error').classList.add('hidden');
    document.getElementById('phone-modal-info').classList.add('hidden');
    document.getElementById('phone-modal-number').value = '';
    document.getElementById('phone-modal-code').value = '';
}
function closePhoneBindModal() {
    const modal = document.getElementById('phone-bind-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
function phoneModalError(message) {
    const el = document.getElementById('phone-modal-error');
    el.textContent = message;
    el.classList.remove('hidden');
    document.getElementById('phone-modal-info').classList.add('hidden');
}
function phoneModalInfo(message) {
    const el = document.getElementById('phone-modal-info');
    el.textContent = message;
    el.classList.remove('hidden');
    document.getElementById('phone-modal-error').classList.add('hidden');
}
function extractErrorMessage(data, fallback) {
    const errors = data && data.errors;
    if (errors) {
        const first = Object.values(errors)[0];
        if (Array.isArray(first) && first.length > 0) return first[0];
    }
    return (data && data.message) || fallback;
}
let phoneCodeCountdown = 0;
function tickPhoneCountdown() {
    const btn = document.getElementById('phone-send-code-btn');
    if (phoneCodeCountdown <= 0) {
        btn.disabled = false;
        btn.textContent = {{ js(__('auth.get_sms_code')) }};
        return;
    }
    btn.disabled = true;
    btn.textContent = phoneCodeCountdown + 's';
    phoneCodeCountdown--;
    setTimeout(tickPhoneCountdown, 1000);
}
async function sendPhoneBindCode() {
    const phone = document.getElementById('phone-modal-number').value.trim();
    if (!phone) { phoneModalError({{ js(__('validation.phone_required')) }}); return; }
    const btn = document.getElementById('phone-send-code-btn');
    btn.disabled = true;
    try {
        const response = await fetch({{ js(route('sms.send')) }}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ phone: phone, purpose: 'phone_bind' }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            btn.disabled = false;
            phoneModalError(extractErrorMessage(data, {{ js(__('auth.sms_send_failed')) }}));
            return;
        }
        phoneModalInfo(data.message || {{ js(__('auth.sms_code_sent')) }});
        phoneCodeCountdown = 60;
        tickPhoneCountdown();
    } catch (e) {
        btn.disabled = false;
        phoneModalError({{ js(__('auth.sms_send_failed')) }});
    }
}
async function submitPhoneBind() {
    const phone = document.getElementById('phone-modal-number').value.trim();
    const code = document.getElementById('phone-modal-code').value.trim();
    if (!phone) { phoneModalError({{ js(__('validation.phone_required')) }}); return; }
    if (!code) { phoneModalError({{ js(__('validation.sms_code_required')) }}); return; }
    const btn = document.getElementById('phone-bind-submit-btn');
    btn.disabled = true;
    try {
        const response = await fetch({{ js(route('account.phone.bind')) }}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ phone: phone, sms_code: code }),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            btn.disabled = false;
            phoneModalError(extractErrorMessage(data, {{ js(__('auth.sms_code_invalid')) }}));
            return;
        }
        phoneModalInfo(data.message || {{ js(__('account.phone_bound')) }});
        setTimeout(() => { window.location.reload(); }, 800);
    } catch (e) {
        btn.disabled = false;
        phoneModalError({{ js(__('auth.sms_code_invalid')) }});
    }
}
document.getElementById('phone-bind-modal').addEventListener('click', (event) => {
    if (event.target.id === 'phone-bind-modal') closePhoneBindModal();
});
</script>
