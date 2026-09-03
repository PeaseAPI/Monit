{{-- 支付网关密钥（M25）：白名单键直接写入 .env，保存后自动 config:clear --}}
@php use App\Support\PaymentGatewayCatalog; @endphp
<div class="space-y-4">
    <div class="rounded-xl bg-blue-50 border border-blue-100 p-4 text-xs text-blue-700 leading-relaxed">
        {{ __('admin.payment_gateways_desc') }}<br>
        {{ __('admin.payment_gateways_clear_hint') }}
    </div>

    @foreach (PaymentGatewayCatalog::gateways() as $gateway => $meta)
        @php
            $sigOk = true;
            foreach ($meta['webhook_keys'] as $wk) {
                if (empty($settings[$wk])) { $sigOk = false; break; }
            }
        @endphp
        <details class="rounded-xl border border-zinc-200 bg-white" @if($loop->first) open @endif>
            <summary class="flex cursor-pointer items-center justify-between px-4 py-3 text-sm font-medium text-zinc-800">
                <span>{{ __("admin.gateway_{$gateway}") }}</span>
                @if ($meta['webhook_keys'] !== [])
                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $sigOk ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                        {{ $sigOk ? __('admin.gateway_sig_ok') : __('admin.gateway_sig_missing') }}
                    </span>
                @endif
            </summary>
            <div class="space-y-3 border-t border-zinc-100 px-4 py-4">
                @foreach ($meta['keys'] as $envKey => $type)
                    <div>
                        <label class="block text-xs font-mono font-medium text-zinc-500">{{ $envKey }}</label>
                        @if ($type === 'bool')
                            <select name="{{ $envKey }}" class="form-input">
                                <option value="true" @if(old($envKey, $settings[$envKey] ?? 'true') === 'true') selected @endif>true</option>
                                <option value="false" @if(old($envKey, $settings[$envKey] ?? 'true') === 'false') selected @endif>false</option>
                            </select>
                        @elseif ($type === 'password')
                            {{-- 机密键：value 不回显当前值（防 HTML 源码/缓存/XSS 泄露），仅掩码提示后 4 位；
                                 空提交 = 保持不变（见 AdminSettings::updatePaymentGateways） --}}
                            @php
                                $currentSecret = (string) ($settings[$envKey] ?? '');
                                $secretMask = $currentSecret !== '' ? str_repeat('•', 8).substr($currentSecret, -4) : '';
                            @endphp
                            <div class="mt-1 flex">
                                <input type="password" name="{{ $envKey }}" value="{{ old($envKey) }}" autocomplete="off"
                                    placeholder="{{ $secretMask }}" class="form-input font-mono">
                                <button type="button" onclick="const i=this.previousElementSibling; i.type=i.type==='password'?'text':'password'" class="ml-2 rounded-lg border border-zinc-200 px-3 text-xs text-zinc-500 hover:bg-zinc-50">👁</button>
                            </div>
                            @if ($currentSecret !== '')
                                <label class="mt-1 flex items-center gap-1.5 text-xs text-zinc-400">
                                    <input type="checkbox" name="{{ $envKey }}__clear" class="rounded border-zinc-300">
                                    {{ __('admin.payment_gateways_clear_secret') }}
                                </label>
                            @endif
                        @else
                            <input type="text" name="{{ $envKey }}" value="{{ old($envKey, $settings[$envKey] ?? '') }}" autocomplete="off"
                                class="mt-1 w-full rounded-lg border border-zinc-300 px-3 py-2 font-mono text-sm">
                        @endif
                    </div>
                @endforeach
            </div>
        </details>
    @endforeach
</div>
