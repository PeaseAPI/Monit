{{-- AI 助手设置（规格书 §12.6：国内大模型统一接入） --}}
@php($providers = \App\Services\Ai\AiService::providers())
<div class="space-y-4">
    {{-- 用途说明（用户反馈 #24：说明 AI 助手配置完成后在哪里生效） --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 text-xs leading-relaxed text-blue-800">
        <p class="font-semibold">{{ __('admin.ai_usage_title') }}</p>
        <ul class="mt-1.5 list-inside list-disc space-y-0.5">
            <li>{{ __('admin.ai_usage_seo_audit') }}</li>
            <li>{{ __('admin.ai_usage_insights') }}</li>
            <li>{{ __('admin.ai_usage_keyword') }}</li>
        </ul>
        <p class="mt-1.5 text-blue-600">{{ __('admin.ai_usage_hint') }}</p>
    </div>

    <div class="flex items-center gap-3"><input type="checkbox" name="ai_is_enabled" value="1" {{ old('ai_is_enabled', ($settings['ai.ai_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.ai_is_enabled') }}</label></div>

    <div>
        <label class="block text-sm font-medium text-zinc-700">{{ __('admin.ai_provider') }}</label>
        <select name="ai_provider" class="form-input">
            @foreach(\App\Services\Ai\AiService::PROVIDERS as $provider)
                <option value="{{ $provider }}" {{ old('ai_provider', $settings['ai.ai_provider'] ?? 'log') === $provider ? 'selected' : '' }}>{{ $providers[$provider]['label'] ?? $provider }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.ai_provider_hint') }}</p>
    </div>

    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.ai_api_key') }}</label>
        <input type="password" name="ai_api_key" value="{{ old('ai_api_key', $settings['ai.ai_api_key'] ?? '') }}" class="form-input" autocomplete="new-password">
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.ai_api_key_hint') }}</p></div>

    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.ai_model') }}</label>
        <input type="text" name="ai_model" value="{{ old('ai_model', $settings['ai.ai_model'] ?? '') }}" class="form-input" placeholder="{{ $providers[old('ai_provider', $settings['ai.ai_provider'] ?? 'log')]['default_model'] ?? '' }}">
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.ai_model_hint') }}</p></div>

    <div><label class="block text-sm font-medium text-zinc-700">{{ __('admin.ai_base_url') }}</label>
        <input type="text" name="ai_base_url" value="{{ old('ai_base_url', $settings['ai.ai_base_url'] ?? '') }}" class="form-input" placeholder="https://api.deepseek.com/v1">
        <p class="mt-1 text-xs text-zinc-400">{{ __('admin.ai_base_url_hint') }}</p></div>

    <div class="grid grid-cols-3 gap-4">
        <div><label class="block text-sm text-zinc-600">{{ __('admin.ai_temperature') }}</label>
            <input type="number" step="0.1" min="0" max="2" name="ai_temperature" value="{{ old('ai_temperature', $settings['ai.ai_temperature'] ?? 0.7) }}" class="form-input"></div>
        <div><label class="block text-sm text-zinc-600">{{ __('admin.ai_max_tokens') }}</label>
            <input type="number" min="16" max="32768" name="ai_max_tokens" value="{{ old('ai_max_tokens', $settings['ai.ai_max_tokens'] ?? 1024) }}" class="form-input"></div>
        <div><label class="block text-sm text-zinc-600">{{ __('admin.ai_timeout') }}</label>
            <input type="number" min="5" max="300" name="ai_timeout" value="{{ old('ai_timeout', $settings['ai.ai_timeout'] ?? 60) }}" class="form-input"></div>
    </div>

    <div class="flex items-center gap-3"><input type="checkbox" name="ai_insights_is_enabled" value="1" {{ old('ai_insights_is_enabled', ($settings['ai.ai_insights_is_enabled'] ?? 'false') === 'true') ? 'checked' : '' }}><label class="text-sm">{{ __('admin.ai_insights_is_enabled') }}</label></div>
</div>
