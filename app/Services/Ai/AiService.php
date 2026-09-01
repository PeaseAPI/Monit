<?php

namespace App\Services\Ai;

use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AI 接入服务（规格书 §12.6：国内大模型统一接入）
 *
 * 服务商（settings ai.ai_provider）：
 * - aliyun_bailian   阿里百炼（通义千问 DashScope OpenAI 兼容模式）
 * - tencent_hunyuan  腾讯混元（OpenAI 兼容端点）
 * - volcengine_ark   火山方舟（豆包，OpenAI 兼容端点，model 可填接入点 ID）
 * - openai_compatible 自定义 OpenAI 兼容网关（DeepSeek / Kimi / GLM 等）
 * - log              开发调试驱动（不发出真实请求）
 *
 * 协议统一为 POST {base_url}/chat/completions + Bearer {api_key}。
 * 凭据存管理后台「AI 助手」设置组（settings ai.*），与 SMS/OSS 一致不落 .env。
 */
class AiService
{
    public const PROVIDERS = ['aliyun_bailian', 'tencent_hunyuan', 'volcengine_ark', 'openai_compatible', 'log'];

    /** AI 功能总开关 */
        public static function isEnabled(): bool
    {
        return filter_var(Settings::get('ai.ai_is_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** 统计页「AI 洞察」用例开关 */
    public static function insightsEnabled(): bool
    {
        return static::isEnabled()
            && filter_var(Settings::get('ai.ai_insights_is_enabled', false), FILTER_VALIDATE_BOOLEAN);
    }

    /** 当前服务商（非法值回退 log） */
    public static function provider(): string
    {
        $provider = (string) Settings::get('ai.ai_provider', '');

        if ($provider === '') {
            $provider = (string) config('monit.ai.default_provider', 'log');
        }

        return in_array($provider, self::PROVIDERS, true) ? $provider : 'log';
    }

    /** 服务商预设（config monit.ai.providers） */
    public static function providers(): array
    {
        return (array) config('monit.ai.providers', []);
    }

    /** 端点：预设 base_url 之上允许 settings 覆盖（openai_compatible 必填） */
    public static function baseUrl(): string
    {
        $preset = (string) (self::providers()[static::provider()]['base_url'] ?? '');
        $override = rtrim(trim((string) Settings::get('ai.ai_base_url', '')), '/');

        return $override !== '' ? $override : rtrim($preset, '/');
    }

    public static function model(): string
    {
        $model = trim((string) Settings::get('ai.ai_model', ''));

        return $model !== '' ? $model : (string) (self::providers()[static::provider()]['default_model'] ?? '');
    }

    public static function isConfigured(): bool
    {
        if (static::provider() === 'log') {
            return true;
        }

        if (static::provider() === 'openai_compatible') {
            return static::baseUrl() !== '' && trim((string) Settings::get('ai.ai_api_key', '')) !== '';
        }

        return trim((string) Settings::get('ai.ai_api_key', '')) !== '';
    }

    /**
     * 统一对话入口
     *
     * @param  string  $prompt  用户消息
     * @param  string|null  $system  系统指令（角色设定）
     * @param  array{temperature?:float,max_tokens?:int}  $options
     * @return array{ok:bool,content:string,error:?string,provider:string,model:string,usage:array}
     */
    public static function chat(string $prompt, ?string $system = null, array $options = []): array
    {
        $provider = static::provider();
        $model = static::model();

        $result = ['ok' => false, 'content' => '', 'error' => null, 'provider' => $provider, 'model' => $model, 'usage' => []];

        if (! static::isEnabled()) {
            $result['error'] = 'ai_disabled';

            return $result;
        }

        // log 调试驱动：不发出真实请求，返回固定内容便于联调
        if ($provider === 'log') {
            $content = '[monit-ai:log] '.($system !== null ? "({$system}) " : '').mb_substr($prompt, 0, 200);

            Log::channel('stack')->info('monit.ai.chat', ['provider' => 'log', 'model' => $model, 'prompt' => $prompt]);

            return [...$result, 'ok' => true, 'content' => $content];
        }

        if (! static::isConfigured()) {
            $result['error'] = 'ai_not_configured';

            return $result;
        }

        $messages = [];
        if ($system !== null && $system !== '') {
            $messages[] = ['role' => 'system', 'content' => $system];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        try {
            $response = Http::withToken((string) Settings::get('ai.ai_api_key', ''))
                ->timeout((int) Settings::get('ai.ai_timeout', 60))
                ->acceptJson()
                ->post(static::baseUrl().'/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => (float) ($options['temperature'] ?? Settings::get('ai.ai_temperature', 0.7)),
                    'max_tokens' => (int) ($options['max_tokens'] ?? Settings::get('ai.ai_max_tokens', 1024)),
                ]);

            if (! $response->successful()) {
                $result['error'] = 'ai_http_'.$response->status();

                return $result;
            }

            $content = (string) ($response->json('choices.0.message.content') ?? '');

            if ($content === '') {
                $result['error'] = 'ai_empty_response';

                return $result;
            }

            return [
                ...$result,
                'ok' => true,
                'content' => $content,
                'usage' => (array) ($response->json('usage') ?? []),
            ];
        } catch (Throwable $e) {
            report($e);
            $result['error'] = 'ai_request_failed';

            return $result;
        }
    }
}
