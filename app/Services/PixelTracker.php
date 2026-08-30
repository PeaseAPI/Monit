<?php

namespace App\Services;

use App\Models\GoalConversion;
use App\Models\LightweightEvent;
use App\Models\OutboundClick;
use App\Models\SessionEvent;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Monit 像素跟踪核心服务
 * 实现规格书 §4 像素采集协议（advanced + lightweight 双模式）
 */
class PixelTracker
{
    protected Website $website;

    protected Request $request;

    protected array $payload = [];

    protected ?\Closure $skipCallback = null;

    public function __construct(
        protected UserAgentParser $uaParser,
        protected GeoIp $geoIp,
    ) {}

    /**
     * 设置跳过回调（外部决定如何响应被跳过的请求）
     */
    public function onSkip(\Closure $callback): static
    {
        $this->skipCallback = $callback;

        return $this;
    }

    /**
     * 主入口：处理一次像素上报
     */
    public function handle(Website $website, Request $request): void
    {
        $this->website = $website;
        $this->request = $request;

        // 1. 解析载荷
        $raw = $request->input('data');
        $payload = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($payload) || ! isset($payload['type'])) {
            $this->skip('invalid_payload');

            return;
        }

        $this->payload = $payload;

        // 2. 前置校验（爬虫 / IP 排除 / 站点禁用 / 用户状态 / 限额）
        if (! $this->passesPreChecks()) {
            return;
        }

        // 3. 分流：lightweight / advanced
        if ($website->isLightweight()) {
            $this->handleLightweight();
        } else {
            $this->handleAdvanced();
        }
    }

    /* ---------------------------------------------------------------------
     | 前置校验
     --------------------------------------------------------------------- */

    protected function passesPreChecks(): bool
    {
        // 站点已禁用
        if (! $this->website->is_enabled) {
            $this->skip('website_disabled');

            return false;
        }

        // host 匹配验证（去 www. 前缀比对）
        $urlHost = parse_url($this->payload['url'] ?? '', PHP_URL_HOST) ?: '';
        if ($urlHost !== '' && ! $this->website->matchesHost($urlHost)) {
            $this->skip('host_mismatch');

            return false;
        }

        // 爬虫检测
        if ($this->website->bot_exclusion_is_enabled && $this->uaParser->isCrawler()) {
            $this->skip('crawler');

            return false;
        }

        // IP 排除
        $ip = $this->clientIp();
        if (in_array($ip, $this->website->excludedIpsList(), true)) {
            $this->skip('ip_excluded');

            return false;
        }

        // 所属用户被禁用 / 套餐限额
        $user = $this->website->user;
        if (! $user || $user->status !== 1) {
            $this->skip('user_disabled');

            return false;
        }

        $planSettings = $user->getPlanSettings();
        $limit = $planSettings['sessions_events_limit'] ?? -1;
        if ($limit !== -1 && $this->website->current_month_sessions_events >= $limit) {
            // 标记限额通知（由用户中心展示）
            if (! $this->website->plan_sessions_events_limit_notice) {
                $this->website->forceFill(['plan_sessions_events_limit_notice' => true])->save();
            }

            $this->skip('plan_limit');

            return false;
        }

        return true;
    }

    /* ---------------------------------------------------------------------
     | Lightweight 模式（单表）
     --------------------------------------------------------------------- */

    protected function handleLightweight(): void
    {
        $type = $this->payload['type'];
        $data = $this->payload['data'] ?? [];

        switch ($type) {
            case 'landing_page':
            case 'pageview':
                $this->insertLightweightEvent($type, $data);
                $this->incrementUsage();

                break;

            case 'outbound_click':
                $this->insertOutboundClick(null, null);
                $this->incrementUsage(false);

                break;

            case 'goal_conversion':
                $this->handleGoalConversion(null, null, null);
                $this->incrementUsage(false);

                break;

            default:
                // lightweight 不支持的类型静默忽略
                $this->skip('unsupported_type');
        }
    }

    protected function insertLightweightEvent(string $type, array $data): void
    {
        $geo = $this->geoIp->lookup($this->clientIp());
        [$osName] = $this->uaParser->os();
        [$browserName] = $this->uaParser->browser();
        [$path, $query] = $this->parseUrlPath($data);

        LightweightEvent::create([
            'website_id' => $this->website->website_id,
            'type' => $type,
            'path' => $path,
            'referrer_host' => $this->parseReferrer($data, 'host'),
            'referrer_path' => $this->parseReferrer($data, 'path'),
            'utm_source' => $this->extractUtm($query, 'utm_source'),
            'utm_medium' => $this->extractUtm($query, 'utm_medium'),
            'utm_campaign' => $this->extractUtm($query, 'utm_campaign'),
            'continent_code' => $geo['continent_code'],
            'country_code' => $geo['country_code'],
            'city_name' => $geo['city_name'],
            'os_name' => $osName,
            'browser_name' => $browserName,
            'browser_language' => substr((string) ($data['language'] ?? ''), 0, 16) ?: null,
            'browser_timezone' => substr((string) ($data['timezone'] ?? ''), 0, 64) ?: null,
            'screen_resolution' => $this->parseResolution($data),
            'device_type' => $this->uaParser->deviceType(),
            'theme' => substr((string) ($data['theme'] ?? ''), 0, 8) ?: null,
            'date' => now(),
            'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days')),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Advanced 模式（多表关联）
     --------------------------------------------------------------------- */

    protected function handleAdvanced(): void
    {
        $type = $this->payload['type'];
        $data = $this->payload['data'] ?? [];

        switch ($type) {
            case 'initiate_visitor':
                $this->upsertVisitor($data);

                break;

            case 'landing_page':
            case 'pageview':
                $this->insertSessionEvent($type, $data);
                $this->incrementUsage();

                break;

            case 'click':
            case 'scroll':
            case 'form':
            case 'resize':
                $this->insertEventChild($type, $data);
                $this->incrementUsage(false);

                break;

            case 'outbound_click':
                $visitor = $this->findVisitor();
                $event = $this->findCurrentEvent($visitor);
                $this->insertOutboundClick($visitor?->visitor_id, $event?->event_id);
                $this->incrementUsage(false);

                break;

            case 'goal_conversion':
                $visitor = $this->findVisitor();
                $event = $this->findCurrentEvent($visitor);
                $session = $visitor ? $this->findSession($visitor) : null;
                $this->handleGoalConversion($visitor, $event, $session);
                $this->incrementUsage(false);

                break;

            case 'replays':
                $this->handleReplayChunk();

                break;

            case 'heatmap_snapshot':
                $this->handleHeatmapSnapshot();

                break;

            case 'heatmap_snapshot_click':
                $this->handleHeatmapSnapshotClick();

                break;

            case 'heatmap_snapshot_scroll':
                $this->handleHeatmapSnapshotScroll();

                break;

            default:
                $this->skip('unsupported_type');
        }
    }

    /**
     * initiate_visitor：upsert 访客记录
     */
    protected function upsertVisitor(array $data): void
    {
        $uuidBinary = $this->uuidToBinary($this->payload['visitor_uuid'] ?? '');
        if ($uuidBinary === null) {
            $this->skip('invalid_uuid');

            return;
        }

        $geo = $this->geoIp->lookup($this->clientIp());
        [$osName, $osVersion] = $this->uaParser->os();
        [$browserName, $browserVersion] = $this->uaParser->browser();

        $customParameters = $this->filterCustomParameters($data['custom_parameters'] ?? []);

        WebsiteVisitor::upsert(
            [[
                'website_id' => $this->website->website_id,
                'visitor_uuid_binary' => $uuidBinary,
                'ip' => $this->website->ip_tracking_is_enabled ? $this->clientIp() : null,
                'custom_parameters' => $customParameters ? json_encode($customParameters, JSON_UNESCAPED_UNICODE) : null,
                'continent_code' => $geo['continent_code'],
                'country_code' => $geo['country_code'],
                'city_name' => $geo['city_name'],
                'os_name' => $osName,
                'os_version' => $osVersion,
                'browser_name' => $browserName,
                'browser_version' => $browserVersion,
                'browser_language' => substr((string) ($data['language'] ?? ''), 0, 16) ?: null,
                'browser_timezone' => substr((string) ($data['timezone'] ?? ''), 0, 64) ?: null,
                'screen_resolution' => $this->parseResolution($data),
                'device_type' => $this->uaParser->deviceType(),
                'theme' => substr((string) ($data['theme'] ?? ''), 0, 8) ?: null,
                'date' => now(),
                'last_date' => now(),
            ]],
            ['website_id', 'visitor_uuid_binary'],
            ['last_date', 'ip', 'browser_language', 'browser_timezone', 'screen_resolution', 'device_type', 'theme']
        );
    }

    /**
     * landing_page / pageview：写入会话事件
     */
    protected function insertSessionEvent(string $type, array $data): void
    {
        $visitor = $this->findOrCreateVisitor();
        if (! $visitor) {
            $this->skip('visitor_not_found');

            return;
        }

        $session = $this->findOrCreateSession($visitor);
        if (! $session) {
            $this->skip('session_invalid');

            return;
        }

        [$path, $query] = $this->parseUrlPath($data);

        $event = SessionEvent::create([
            'event_uuid_binary' => $this->uuidToBinary($this->payload['visitor_session_event_uuid'] ?? Uuid::uuid4()->toString()),
            'session_id' => $session->session_id,
            'visitor_id' => $visitor->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type,
            'path' => $path,
            'title' => mb_substr((string) ($data['title'] ?? ''), 0, 512) ?: null,
            'referrer_host' => $this->parseReferrer($data, 'host'),
            'referrer_path' => $this->parseReferrer($data, 'path'),
            'utm_source' => $this->extractUtm($query, 'utm_source'),
            'utm_medium' => $this->extractUtm($query, 'utm_medium'),
            'utm_campaign' => $this->extractUtm($query, 'utm_campaign'),
            'viewport_width' => (int) ($data['viewport']['width'] ?? 0) ?: null,
            'viewport_height' => (int) ($data['viewport']['height'] ?? 0) ?: null,
            'has_bounced' => $type === 'landing_page',
            'date' => now(),
            'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days')),
        ]);

        // pageview 出现 => 该会话未跳出
        if ($type === 'pageview') {
            SessionEvent::where('session_id', $session->session_id)
                ->where('type', 'landing_page')
                ->where('has_bounced', true)
                ->update(['has_bounced' => false]);
        }

        // 更新会话与访客
        $session->increment('total_events');
        $visitor->forceFill([
            'last_date' => now(),
            'last_event_id' => $event->event_id,
        ])->save();

        if ($type === 'landing_page') {
            $visitor->increment('total_sessions');
        }
    }

    /**
     * click/scroll/form/resize：事件子项
     */
    protected function insertEventChild(string $type, array $data): void
    {
        $visitor = $this->findVisitor();
        $event = $this->findCurrentEvent($visitor);

        if (! $event) {
            $this->skip('event_not_found');

            return;
        }

        \App\Models\EventChild::create([
            'event_id' => $event->event_id,
            'session_id' => $event->session_id,
            'visitor_id' => $event->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type,
            'data' => $this->sanitizeEventData($data),
            'count' => 1,
            'date' => now(),
            'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days')),
        ]);
    }

    /**
     * outbound_click：出站点击
     */
    protected function insertOutboundClick(?int $visitorId, ?int $eventId): void
    {
        $url = $this->payload['outbound_url'] ?? '';

        OutboundClick::create([
            'website_id' => $this->website->website_id,
            'event_id' => $eventId,
            'visitor_id' => $visitorId,
            'host' => mb_substr((string) (parse_url($url, PHP_URL_HOST) ?? ''), 0, 256) ?: null,
            'path' => mb_substr((string) (parse_url($url, PHP_URL_PATH) ?? ''), 0, 2048) ?: null,
            'title' => mb_substr((string) ($this->payload['outbound_title'] ?? ''), 0, 512) ?: null,
            'datetime' => now(),
        ]);
    }

    /**
     * goal_conversion：目标转化
     */
    protected function handleGoalConversion(?WebsiteVisitor $visitor, ?SessionEvent $event, ?VisitorSession $session): void
    {
        $goalKey = (string) ($this->payload['goal_key'] ?? '');
        if ($goalKey === '') {
            $this->skip('invalid_goal_key');

            return;
        }

        $goal = $this->website->goals()
            ->where('key', $goalKey)
            ->where('is_enabled', true)
            ->first();

        if (! $goal) {
            $this->skip('goal_not_found');

            return;
        }

        // 同一访客同一目标去重
        if ($visitor) {
            $converted = $visitor->goals_conversions_ids ?? [];
            if (in_array($goal->goal_id, $converted, false)) {
                $this->skip('goal_duplicate');

                return;
            }

            $converted[] = $goal->goal_id;
            $visitor->goals_conversions_ids = array_values($converted);
            $visitor->save();
        }

        GoalConversion::create([
            'goal_id' => $goal->goal_id,
            'event_id' => $event?->event_id,
            'session_id' => $session?->session_id,
            'visitor_id' => $visitor?->visitor_id,
            'website_id' => $this->website->website_id,
            'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days')),
        ]);
    }

    /**
     * replays：回放 chunk（存缓存 store_adapter；offload 由 cron 处理）
     */
    protected function handleReplayChunk(): void
    {
        $sessionIdBinary = $this->uuidToBinary($this->payload['visitor_session_uuid'] ?? '');
        if ($sessionIdBinary === null) {
            $this->skip('invalid_uuid');

            return;
        }

        $session = VisitorSession::where('website_id', $this->website->website_id)
            ->where('session_uuid_binary', $sessionIdBinary)
            ->first();

        if (! $session) {
            $this->skip('session_not_found');

            return;
        }

        // 确保 replay 主记录存在
        $exists = \App\Models\SessionReplay::where('session_id', $session->session_id)->exists();
        if (! $exists) {
            // 回放配额（规格 §10.2：sessions_replays_limit；-1 不限）
            $replayLimit = $this->website->user?->getPlanSettings()['sessions_replays_limit'] ?? 0;
            if ($replayLimit > 0 && $this->website->current_month_sessions_replays >= $replayLimit) {
                $this->skip('replays_limit');

                return;
            }

            \App\Models\SessionReplay::create([
                'session_id' => $session->session_id,
                'visitor_id' => $session->visitor_id,
                'website_id' => $this->website->website_id,
                'datetime' => now(),
            ]);

            $this->website->increment('current_month_sessions_replays');
        }

        // chunk 索引：缓存存储（key = session_replay_keys_{session_id}）
        $cacheKey = "session_replay_keys_{$session->session_id}";
        $keys = Cache::get($cacheKey, []);
        $chunkKey = 'session_replay_chunk_'.md5($session->session_id.'_'.count($keys).'_'.uniqid('', true));
        Cache::put($chunkKey, $this->payload['data'] ?? [], now()->addDays(config('monit.pixel.replays_retention_days')));
        $keys[] = $chunkKey;
        Cache::put($cacheKey, $keys, now()->addDays(config('monit.pixel.replays_retention_days')));
    }

    /**
     * heatmap_snapshot：DOM 快照（规格 §4.4：gzencode 压缩 → heatmaps_snapshots → 更新 heatmaps 尺寸引用）
     */
    protected function handleHeatmapSnapshot(): void
    {
        $heatmap = $this->findEnabledHeatmap();
        if (! $heatmap) {
            return;
        }

        $device = $this->uaParser->deviceType(); // desktop / tablet / mobile
        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        $json = json_encode($this->payload['data'] ?? [], JSON_UNESCAPED_UNICODE);
        $compressed = gzencode((string) $json, 9);

        $snapshot = \App\Models\HeatmapSnapshot::create([
            'heatmap_id' => $heatmap->heatmap_id,
            'website_id' => $this->website->website_id,
            'type' => $device,
            'data' => $compressed,
            'date' => now()->toDateString(),
        ]);

        $heatmap->forceFill([
            "snapshot_id_{$device}" => $snapshot->snapshot_id,
            "{$device}_size" => strlen((string) $compressed),
        ])->save();

        $this->website->increment('current_month_sessions_replays');
    }

    /**
     * heatmap_snapshot_click：点击坐标（x/y_normalized 0-100，count 1-10 rage click）
     */
    protected function handleHeatmapSnapshotClick(): void
    {
        $heatmap = $this->findEnabledHeatmap();
        if (! $heatmap) {
            return;
        }

        $device = $this->currentDeviceColumn($heatmap);
        if (! $device) {
            $this->skip('heatmap_snapshot_missing');

            return;
        }

        $x = max(0, min(100, (float) ($this->payload['x_normalized'] ?? 0)));
        $y = max(0, min(100, (float) ($this->payload['y_normalized'] ?? 0)));
        $count = max(1, min(10, (int) ($this->payload['count'] ?? 1)));

        \App\Models\HeatmapSnapshotClick::create([
            'website_id' => $this->website->website_id,
            'snapshot_id' => $heatmap->{"snapshot_id_{$device}"},
            'x_normalized' => $x,
            'y_normalized' => $y,
            'count' => $count,
            'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days'))->toDateString(),
            'datetime' => now(),
        ]);
    }

    /**
     * heatmap_snapshot_scroll：滚动深度（max_scroll 0-100 按 10 取整，同事件取最大值）
     */
    protected function handleHeatmapSnapshotScroll(): void
    {
        $heatmap = $this->findEnabledHeatmap();
        if (! $heatmap) {
            return;
        }

        $device = $this->currentDeviceColumn($heatmap);
        if (! $device) {
            $this->skip('heatmap_snapshot_missing');

            return;
        }

        $maxScroll = (int) round(max(0, min(100, (int) ($this->payload['max_scroll'] ?? 0))) / 10) * 10;

        $uuidBinary = $this->uuidToBinary($this->payload['visitor_session_event_uuid'] ?? '');
        if ($uuidBinary === null) {
            $this->skip('invalid_uuid');

            return;
        }

        \App\Models\HeatmapSnapshotScroll::upsert(
            [[
                'website_id' => $this->website->website_id,
                'snapshot_id' => $heatmap->{"snapshot_id_{$device}"},
                'event_uuid_binary' => $uuidBinary,
                'max_scroll' => $maxScroll,
                'expiration_date' => now()->addDays(config('monit.pixel.events_retention_days'))->toDateString(),
                'last_datetime' => now(),
                'datetime' => now(),
            ]],
            ['event_uuid_binary'],
            ['max_scroll', 'last_datetime']
        );
    }

    /**
     * 查找本网站启用中的热图（payload.heatmap_id）
     */
    protected function findEnabledHeatmap(): ?\App\Models\Heatmap
    {
        $heatmapId = (int) ($this->payload['heatmap_id'] ?? 0);

        $heatmap = \App\Models\Heatmap::where('website_id', $this->website->website_id)
            ->where('heatmap_id', $heatmapId)
            ->where('is_enabled', true)
            ->first();

        if (! $heatmap) {
            $this->skip('heatmap_not_found');
        }

        return $heatmap;
    }

    /**
     * 当前设备列名 + 对应 snapshot_id 是否已生成
     * @return string|null desktop|tablet|mobile 或 null（快照未采集）
     */
    protected function currentDeviceColumn(\App\Models\Heatmap $heatmap): ?string
    {
        $device = $this->uaParser->deviceType();
        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        return $heatmap->{"snapshot_id_{$device}"} ? $device : null;
    }

    /* ---------------------------------------------------------------------
     | 查找辅助
     --------------------------------------------------------------------- */

    protected function findVisitor(): ?WebsiteVisitor
    {
        $uuidBinary = $this->uuidToBinary($this->payload['visitor_uuid'] ?? '');
        if ($uuidBinary === null) {
            return null;
        }

        return WebsiteVisitor::where('website_id', $this->website->website_id)
            ->where('visitor_uuid_binary', $uuidBinary)
            ->first();
    }

    protected function findOrCreateVisitor(): ?WebsiteVisitor
    {
        $visitor = $this->findVisitor();

        if ($visitor) {
            return $visitor;
        }

        // 容错：SDK 未先发送 initiate_visitor 时自动补建
        $this->upsertVisitor($this->payload['data'] ?? []);

        return $this->findVisitor();
    }

    protected function findSession(WebsiteVisitor $visitor): ?VisitorSession
    {
        $uuidBinary = $this->uuidToBinary($this->payload['visitor_session_uuid'] ?? '');
        if ($uuidBinary === null) {
            return null;
        }

        return VisitorSession::where('website_id', $this->website->website_id)
            ->where('visitor_id', $visitor->visitor_id)
            ->where('session_uuid_binary', $uuidBinary)
            ->first();
    }

    protected function findOrCreateSession(WebsiteVisitor $visitor): ?VisitorSession
    {
        $session = $this->findSession($visitor);

        if ($session) {
            // 会话超时 => 开新会话
            $timeout = (int) config('monit.pixel.session_timeout');
            if ($session->date && $session->date->diffInSeconds(now()) <= $timeout) {
                return $session;
            }
        }

        return VisitorSession::create([
            'session_uuid_binary' => $this->uuidToBinary($this->payload['visitor_session_uuid'] ?? Uuid::uuid4()->toString()),
            'visitor_id' => $visitor->visitor_id,
            'website_id' => $this->website->website_id,
            'date' => now(),
            'total_events' => 0,
        ]);
    }

    protected function findCurrentEvent(?WebsiteVisitor $visitor): ?SessionEvent
    {
        if (! $visitor) {
            return null;
        }

        $eventUuidBinary = $this->uuidToBinary($this->payload['visitor_session_event_uuid'] ?? '');
        if ($eventUuidBinary !== null) {
            $event = SessionEvent::where('website_id', $this->website->website_id)
                ->where('event_uuid_binary', $eventUuidBinary)
                ->first();

            if ($event) {
                return $event;
            }
        }

        // 降级：访客最后事件
        return SessionEvent::where('visitor_id', $visitor->visitor_id)
            ->where('website_id', $this->website->website_id)
            ->orderByDesc('event_id')
            ->first();
    }

    /* ---------------------------------------------------------------------
     | 解析辅助
     --------------------------------------------------------------------- */

    protected function clientIp(): string
    {
        return $this->request->ip() ?? '0.0.0.0';
    }

    protected function uuidToBinary(?string $uuid): ?string
    {
        if (! $uuid) {
            return null;
        }

        try {
            return Uuid::fromString($uuid)->getBytes();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 解析页面 path（按设置裁剪 query）
     *
     * @return array{0: string, 1: string}
     */
    protected function parseUrlPath(array $data): array
    {
        $url = (string) ($data['url'] ?? $this->payload['url'] ?? '');
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?: '');

        if (! $this->website->query_parameters_tracking_is_enabled) {
            $query = '';
        }

        return [mb_substr($path, 0, 2048), $query];
    }

    /**
     * 解析 referrer 的 host 或 path
     */
    protected function parseReferrer(array $data, string $part): ?string
    {
        $referrer = (string) ($data['referrer'] ?? '');
        if ($referrer === '') {
            return null;
        }

        if ($part === 'host') {
            return mb_substr((string) (parse_url($referrer, PHP_URL_HOST) ?: ''), 0, 256) ?: null;
        }

        return mb_substr((string) (parse_url($referrer, PHP_URL_PATH) ?: ''), 0, 2048) ?: null;
    }

    protected function extractUtm(string $query, string $key): ?string
    {
        if ($query === '') {
            return null;
        }

        parse_str($query, $params);
        $value = $params[$key] ?? null;

        return $value ? mb_substr((string) $value, 0, 256) : null;
    }

    protected function parseResolution(array $data): ?string
    {
        $width = (int) ($data['resolution']['width'] ?? 0);
        $height = (int) ($data['resolution']['height'] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return $width.'x'.$height;
    }

    protected function filterCustomParameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $filtered = [];
        foreach (array_slice($parameters, 0, (int) config('monit.pixel.max_custom_parameters')) as $key => $value) {
            $filtered[mb_substr((string) $key, 0, 64)] = mb_substr((string) $value, 0, 256);
        }

        return $filtered;
    }

    protected function sanitizeEventData(array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        return json_decode(mb_substr((string) $json, 0, 8192), true) ?: [];
    }

    /**
     * 用量更新：所有事件 current_month_sessions_events++；
     * landing_page/pageview 额外 last_24/7_days_pageviews++
     */
    protected function incrementUsage(bool $pageview = true): void
    {
        $updates = ['current_month_sessions_events' => DB::raw('current_month_sessions_events + 1')];

        if ($pageview) {
            $updates['last_24_hours_pageviews'] = DB::raw('last_24_hours_pageviews + 1');
            $updates['last_7_days_pageviews'] = DB::raw('last_7_days_pageviews + 1');
        }

        Website::where('website_id', $this->website->website_id)->update($updates);
    }

    protected function skip(string $reason): void
    {
        if ($this->skipCallback) {
            ($this->skipCallback)($reason);
        }
    }
}
