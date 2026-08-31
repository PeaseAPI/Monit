<?php

namespace App\Services\Seo;

use App\Jobs\Seo\SeoAiSummaryJob;
use App\Models\SeoAudit;
use App\Models\SeoAuditArchive;
use App\Models\User;
use App\Models\Website;
use App\Services\PlanLimitService;
use App\Services\Seo\Tests\ContentTests;
use App\Services\Seo\Tests\LinkTests;
use App\Services\Seo\Tests\MetaTests;
use App\Services\Seo\Tests\MiscTests;
use App\Services\Seo\Tests\PerformanceTests;
use App\Services\Seo\Tests\SecurityTests;
use App\Support\Settings;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * SEO 审计引擎：抓取 → 解析 → 逐测试执行 → 评分 → 落库
 *
 * 用法：app(AuditEngine::class)->run($url, $user, 'single')
 * 批量（sitemap/bulk）由 RunSeoAuditJob 队列分批调用本引擎
 */
class AuditEngine
{
    /** 测试组注册（键 => [实例, 方法]） */
    protected array $groups = [];

    public function __construct()
    {
        foreach ([MetaTests::class, ContentTests::class, PerformanceTests::class, SecurityTests::class, LinkTests::class, MiscTests::class] as $group) {
            $instance = new $group;

            foreach ($instance->handles() as $key => $method) {
                $this->groups[$key] = [$instance, $method];
            }
        }
    }

    public function run(string $url, ?User $user = null, string $type = 'single', array $options = []): SeoAudit
    {
        $url = static::normalizeUrl($url);
        $host = (string) parse_url($url, PHP_URL_HOST);

        $audit = new SeoAudit([
            'user_id' => $user?->user_id,
            'url' => $url,
            'host' => strtolower(preg_replace('/^www\./', '', $host) ?: $host),
            'type' => $type,
            'share_token' => Str::lower(Str::random(32)),
            'uploader_key' => $options['uploader_key'] ?? null,
        ]);

        // 匹配用户网站（同 host 自动挂接，流量与 SEO 数据同源）
        if ($user && ! isset($options['website_id'])) {
            $audit->website_id = $user->websites()->where('host', $audit->host)->value('website_id');
        } else {
            $audit->website_id = $options['website_id'] ?? null;
        }

        try {
            $context = $this->fetchContext($url);
            $results = $this->executeTests($context);
            $score = AuditScore::calculate($results);

            $audit->fill([
                'status' => 'completed',
                'score' => $score['score'],
                'category_scores' => $score['category_scores'],
                'total_tests' => count($results),
                'passed_tests' => $score['passed'],
                'major_issues' => $score['major'],
                'moderate_issues' => $score['moderate'],
                'minor_issues' => $score['minor'],
                'response_time_ms' => $context->responseTimeMs,
                'page_size_bytes' => $context->sizeBytes,
                'results' => $results,
            ]);
        } catch (Throwable $e) {
            report($e);

            $audit->fill([
                'status' => 'failed',
                'error' => mb_substr($e->getMessage(), 0, 500) ?: '请求失败',
            ]);
        }

        $audit->save();

        if ($audit->status === 'completed') {
            $this->archive($audit);
        }

        $website = $audit->website_id ? Website::find($audit->website_id) : null;

        if ($website) {
            $this->refreshWebsiteAggregates($website);
        }

        // 定时复审触发通知；AI 摘要按需（with_ai）或定时任务
        if (($options['scheduled'] ?? false) && $user) {
            app(NotificationDispatcher::class)->dispatchForAudit($audit, $website);
            $this->maybeDispatchAi($audit, $user);
        } elseif ($user && ($options['with_ai'] ?? false)) {
            $this->maybeDispatchAi($audit, $user);
        }

        return $audit;
    }

    /**
     * 抓取目标页并预取 robots / sitemap / SSL 探测
     */
    protected function fetchContext(string $url): AuditContext
    {
        $timeout = (int) Settings::get('seo.seo_request_timeout', 20);
        $ua = (string) Settings::get('seo.seo_request_user_agent', 'Mozilla/5.0 (compatible; MonitBot/1.0)');

        $started = microtime(true);
        $response = $this->request($url, $timeout, $ua);
        $elapsed = (int) round((microtime(true) - $started) * 1000);

        $html = (string) $response->body();
        $scheme = strtolower((string) (parse_url($url, PHP_URL_SCHEME) ?: 'https'));
        $host = (string) parse_url($url, PHP_URL_HOST);

        if (trim($html) === '' && $response->status() === 0) {
            throw new RuntimeException('目标站点无法访问');
        }

        $context = new AuditContext(
            url: $url,
            scheme: $scheme,
            host: strtolower(preg_replace('/^www\./', '', $host) ?: $host),
            html: $html,
            headers: $response->headers(),
            statusCode: $response->status() === 0 ? 503 : $response->status(),
            responseTimeMs: max(1, $elapsed),
            sizeBytes: strlen($html),
            sslInfo: $scheme === 'https' ? $this->probeSsl($host) : null,
        );

        $context->extra['robots_exists'] = $this->remoteExists("{$scheme}://{$host}/robots.txt", $timeout, $ua);
        $context->extra['sitemap_exists'] = $this->remoteExists("{$scheme}://{$host}/sitemap.xml", $timeout, $ua);

        return $context;
    }

    /**
     * 带二次校验的请求（反爬偶发失败自动重试一次）
     */
    protected function request(string $url, int $timeout, string $ua, int $attempt = 0): Response
    {
        $response = Http::withHeaders(['User-Agent' => $ua])
            ->timeout($timeout)
            ->withOptions(['verify' => false])
            ->get($url);

        $doubleCheck = in_array(Settings::get('seo.seo_double_check'), [true, 'true', null], true);

        if ($attempt === 0 && in_array($response->status(), [0, 500, 502, 503, 504]) && $doubleCheck) {
            usleep(((int) Settings::get('seo.seo_double_check_wait', 2)) * 1000000);

            return $this->request($url, $timeout, $ua, 1);
        }

        return $response;
    }

    protected function remoteExists(string $url, int $timeout, string $ua): bool
    {
        try {
            $response = Http::withHeaders(['User-Agent' => $ua])->timeout(min(10, $timeout))->get($url);

            return $response->successful() && strlen((string) $response->body()) > 0;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * SSL 证书探测（stream socket 纯实现，无扩展依赖）
     *
     * @return array{valid:bool, valid_to?:string}|null
     */
    protected function probeSsl(string $host): ?array
    {
        $context = stream_context_create([
            'ssl' => ['capture_peer_cert' => true, 'verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $socket = @stream_socket_client("ssl://{$host}:443", $errorCode, $errorString, 10, STREAM_CLIENT_CONNECT, $context);

        if ($socket === false) {
            return ['valid' => false];
        }

        $params = stream_context_get_params($socket);
        fclose($socket);

        $cert = $params['options']['ssl']['peer_certificate'] ?? null;

        if ($cert === null) {
            return ['valid' => false];
        }

        return [
            'valid' => $cert->validTo_time_t > time(),
            'valid_to' => date('Y-m-d', (int) $cert->validTo_time_t),
        ];
    }

    /**
     * 逐测试执行：注册表过滤 + 异常隔离（单测试崩溃不影响整份报告）
     */
    protected function executeTests(AuditContext $context): array
    {
        $results = [];

        foreach (AuditTestRegistry::all() as $key => $meta) {
            $handler = $this->groups[$key] ?? null;

            if ($handler === null) {
                continue; // 未实现的测试不占分母
            }

            try {
                $row = call_user_func($handler, $context);
            } catch (Throwable $e) {
                $row = ['passed' => false, 'value' => '执行异常'];
            }

            $results[$key] = [
                'passed' => (bool) ($row['passed'] ?? false),
                'importance' => $meta['importance'],
                'category' => $meta['category'],
                'value' => (string) ($row['value'] ?? ''),
                'detail' => (string) ($row['detail'] ?? ''),
            ];
        }

        return $results;
    }

    /**
     * 历史快照（分数趋势数据源）
     */
    protected function archive(SeoAudit $audit): void
    {
        SeoAuditArchive::create([
            'seo_audit_id' => $audit->seo_audit_id,
            'website_id' => $audit->website_id,
            'user_id' => $audit->user_id,
            'score' => $audit->score,
            'snapshot' => [
                'score' => $audit->score,
                'category_scores' => $audit->category_scores,
                'major' => $audit->major_issues,
                'moderate' => $audit->moderate_issues,
                'minor' => $audit->minor_issues,
                'results_hash' => md5(json_encode($audit->results)),
            ],
            'created_at' => now(),
        ]);
    }

    /**
     * 刷新网站 SEO 聚合并排期下一次复审
     */
    protected function refreshWebsiteAggregates(Website $website): void
    {
        $completed = $website->seoAudits()->where('status', 'completed');

        $website->update([
            'seo_avg_score' => (int) round((clone $completed)->avg('score') ?? 0),
            'seo_total_audits' => (clone $completed)->count(),
            'seo_last_audit_at' => now(),
            'seo_next_audit_at' => static::nextRunTime($website->seo_audit_check_interval),
        ]);
    }

    /**
     * 复审间隔映射到下次执行时间
     */
    public static function nextRunTime(?string $interval): ?CarbonInterface
    {
        return match ($interval) {
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            'monthly' => now()->addMonth(),
            'every3months' => now()->addMonths(3),
            'every6months' => now()->addMonths(6),
            'yearly' => now()->addYear(),
            default => null,
        };
    }

    protected function maybeDispatchAi(SeoAudit $audit, User $user): void
    {
        if ($audit->status !== 'completed') {
            return;
        }

        if (app(PlanLimitService::class)->isFeatureEnabled($user, 'seo_ai_is_enabled')) {
            SeoAiSummaryJob::dispatch($audit);
        }
    }

    /**
     * URL 归一化：补协议、去首尾空白
     */
    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        return $url;
    }
}
