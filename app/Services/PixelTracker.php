<?php

namespace App\Services;

use App\Models\EventChild;
use App\Models\GoalConversion;
use App\Models\Heatmap;
use App\Models\HeatmapSnapshot;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\LightweightEvent;
use App\Models\OutboundClick;
use App\Models\SessionEvent;
use App\Models\SessionReplay;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use App\Support\Typed;
use Illuminate\Database\UniqueConstraintViolationException;
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

    /** @var array<string, mixed> */
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

        // 绑定本次请求的 User-Agent（容器注入的实例未携带 UA，需按请求重建，
        // 否则 os_name / browser_name / 爬虫过滤将全部失效）
        $this->uaParser = UserAgentParser::make($request->userAgent());

        // 1. 解析载荷
        $raw = $request->input('data');
        $payload = is_string($raw) ? json_decode($raw, true) : $raw;

        if (! is_array($payload) || ! isset($payload['type'])) {
            $this->skip('invalid_payload');

            return;
        }

        /** @var array<string, mixed> $payload */
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
        $urlHost = parse_url(Typed::string($this->payload['url'] ?? ''), PHP_URL_HOST) ?: '';
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
        $type = Typed::string($this->payload['type']);
        $data = Typed::arr($this->payload['data'] ?? []);

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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function insertLightweightEvent(string $type, array $data): void
    {
        $geo = $this->geoIp->lookup($this->clientIp());
        [$osName] = $this->uaParser->os();
        [$browserName] = $this->uaParser->browser();
        [$path, $query] = $this->parseUrlPath($data);

        LightweightEvent::create([
            'website_id' => $this->website->website_id,
            // 访客标识（访客明细与旅程：按 uuid 聚合列表 / 行为时间线）
            'visitor_uuid' => $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_uuid'] ?? null)),
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
            'browser_language' => substr(Typed::string($data['language'] ?? ''), 0, 16) ?: null,
            'browser_timezone' => substr(Typed::string($data['timezone'] ?? ''), 0, 64) ?: null,
            'screen_resolution' => $this->parseResolution($data),
            'device_type' => $this->uaParser->deviceType(),
            'theme' => substr(Typed::string($data['theme'] ?? ''), 0, 8) ?: null,
            'date' => now(),
            'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days'))),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Advanced 模式（多表关联）
     --------------------------------------------------------------------- */

    protected function handleAdvanced(): void
    {
        $type = Typed::string($this->payload['type']);
        $data = Typed::arr($this->payload['data'] ?? []);

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
     *
     * @param  array<string, mixed>  $data
     */
    protected function upsertVisitor(array $data): void
    {
        $uuidBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_uuid'] ?? null));
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
                'browser_language' => substr(Typed::string($data['language'] ?? ''), 0, 16) ?: null,
                'browser_timezone' => substr(Typed::string($data['timezone'] ?? ''), 0, 64) ?: null,
                'screen_resolution' => $this->parseResolution($data),
                'device_type' => $this->uaParser->deviceType(),
                'theme' => substr(Typed::string($data['theme'] ?? ''), 0, 8) ?: null,
                'date' => now(),
                'last_date' => now(),
            ]],
            ['website_id', 'visitor_uuid_binary'],
            // 回访时刷新地理 / UA 数据：早期版本未写入这些列的旧行、
            // 以及库升级后 GeoIP 数据变化的访客都能借此补齐（修复"未知"地区不更新问题）
            ['last_date', 'ip', 'continent_code', 'country_code', 'city_name',
                'os_name', 'os_version', 'browser_name', 'browser_version',
                'browser_language', 'browser_timezone', 'screen_resolution', 'device_type', 'theme']
        );
    }

    /**
     * landing_page / pageview：写入会话事件
     *
     * @param  array<string, mixed>  $data
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
            'event_uuid_binary' => $this->uuidToBinary(Typed::string($this->payload['visitor_session_event_uuid'] ?? Uuid::uuid4()->toString())),
            'session_id' => $session->session_id,
            'visitor_id' => $visitor->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type,
            'path' => $path,
            'title' => mb_substr(Typed::string($data['title'] ?? ''), 0, 512) ?: null,
            'referrer_host' => $this->parseReferrer($data, 'host'),
            'referrer_path' => $this->parseReferrer($data, 'path'),
            'utm_source' => $this->extractUtm($query, 'utm_source'),
            'utm_medium' => $this->extractUtm($query, 'utm_medium'),
            'utm_campaign' => $this->extractUtm($query, 'utm_campaign'),
            'viewport_width' => Typed::int(data_get($data, 'viewport.width')) ?: null,
            'viewport_height' => Typed::int(data_get($data, 'viewport.height')) ?: null,
            'has_bounced' => $type === 'landing_page',
            'date' => now(),
            'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days'))),
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
     *
     * @param  array<string, mixed>  $data
     */
    protected function insertEventChild(string $type, array $data): void
    {
        $visitor = $this->findVisitor();
        $event = $this->findCurrentEvent($visitor);

        if (! $event) {
            $this->skip('event_not_found');

            return;
        }

        // 事件子项配额（规格 §10.2：events_children_limit；-1 不限、显式配置 0=无此功能）。
        // 原实现完全不检查且 current_month_events_children 从未递增——M22 通知
        // Cron（websites_events_children_notice）的比较恒为假，通知与配额双双失效。
        // 判定对齐 persistReplayChunk 的回放配额：0 或超限一律拒收；
        // 缺键 ?? -1 = 不限（custom 用户 plan_settings 不与 plan_defaults 合并）。
        $planSettings = $this->website->user?->getPlanSettings() ?? [];
        $childLimit = $planSettings['events_children_limit'] ?? -1;
        $childAllowed = ($childLimit === -1)
            || ($childLimit > 0 && $this->website->current_month_events_children < $childLimit);

        if (! $childAllowed) {
            // 0=功能禁用不标记不打扰；超限标记通知（对齐 sessions_replays_limit）
            if ($childLimit > 0 && ! $this->website->plan_events_children_limit_notice) {
                $this->website->forceFill(['plan_events_children_limit_notice' => true])->save();
            }

            $this->skip($childLimit > 0 ? 'event_children_limit' : 'event_children_disabled');

            return;
        }

        EventChild::create([
            'event_id' => $event->event_id,
            'session_id' => $event->session_id,
            'visitor_id' => $event->visitor_id,
            'website_id' => $this->website->website_id,
            'type' => $type,
            'data' => $this->sanitizeEventData($data),
            'count' => 1,
            'date' => now(),
            'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days'))),
        ]);

        // 用量计数：WebsitesLimitNoticeCommand 与配额判定依赖此列
        $this->website->increment('current_month_events_children');
    }

    /**
     * outbound_click：出站点击
     */
    protected function insertOutboundClick(?int $visitorId, ?int $eventId): void
    {
        $url = Typed::string($this->payload['outbound_url'] ?? '');

        OutboundClick::create([
            'website_id' => $this->website->website_id,
            'event_id' => $eventId,
            'visitor_id' => $visitorId,
            'host' => mb_substr((string) (parse_url($url, PHP_URL_HOST) ?? ''), 0, 256) ?: null,
            'path' => mb_substr((string) (parse_url($url, PHP_URL_PATH) ?? ''), 0, 2048) ?: null,
            'title' => mb_substr(Typed::string($this->payload['outbound_title'] ?? ''), 0, 512) ?: null,
            'datetime' => now(),
        ]);
    }

    /**
     * goal_conversion：目标转化
     */
    protected function handleGoalConversion(?WebsiteVisitor $visitor, ?SessionEvent $event, ?VisitorSession $session): void
    {
        $goalKey = Typed::string($this->payload['goal_key'] ?? '');
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
            $visitor->goals_conversions_ids = $converted;
            $visitor->save();
        }

        GoalConversion::create([
            'goal_id' => $goal->goal_id,
            'event_id' => $event?->event_id,
            'session_id' => $session?->session_id,
            'visitor_id' => $visitor?->visitor_id,
            'website_id' => $this->website->website_id,
            'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days'))),
        ]);
    }

    /**
     * replays：回放 chunk（存缓存 store_adapter；offload 由 cron 处理）
     */
    protected function handleReplayChunk(): void
    {
        $sessionIdBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_session_uuid'] ?? null));
        if ($sessionIdBinary === null) {
            $this->skip('invalid_uuid');

            return;
        }

        $session = VisitorSession::where('website_id', $this->website->website_id)
            ->where('session_uuid_binary', $sessionIdBinary)
            ->first();

        if (! $session) {
            // 回放数据先于 session 事件到达时，自动补建 visitor + session（防丟数据）
            $visitor = $this->findOrCreateVisitor();
            if ($visitor) {
                $session = VisitorSession::create([
                    'session_uuid_binary' => $sessionIdBinary,
                    'visitor_id' => $visitor->visitor_id,
                    'website_id' => $this->website->website_id,
                    'date' => now(),
                    'total_events' => 0,
                ]);
            }
        }

        if (! $session) {
            $this->skip('session_not_found');

            return;
        }

        // 解析事件
        $data = $this->payload['data'] ?? [];
        $events = is_array($data) ? ($data['events'] ?? $data) : [];
        if (! is_array($events) || $events === []) {
            $this->skip('empty_replay_events');

            return;
        }
        $events = array_values($events);
        $chunkSize = strlen((string) json_encode($events, JSON_UNESCAPED_UNICODE));

        // 事务 + 行锁：同一会话的多个 chunk 并发到达时，"读取→合并→写回"必须
        // 原子执行，否则两个请求读到同一份旧 data 后互相覆盖（整批事件丢失）。
        // lockForUpdate 串行化同会话回放的追加写。
        //
        // session_id 有唯一索引（2026_09_10 迁移）：MySQL InnoDB 对不存在行的
        // lockForUpdate 走 gap lock 天然防并发首建；sqlite（网页安装向导默认库）
        // 无 gap lock，两个并发请求可能同时走到 create → 撞唯一索引。
        // 捕获后重跑一次：第二次 lockForUpdate 必命中对方已建行，转追加分支，
        // 该 chunk 事件不丢失。
        $skipped = false;

        try {
            $this->persistReplayChunk($session, $events, $chunkSize, $skipped);
        } catch (UniqueConstraintViolationException) {
            $this->persistReplayChunk($session, $events, $chunkSize, $skipped);
        }

        if ($skipped) {
            return;
        }

        // Cache 仍保留（供 OffloadCommand 中间缓冲 & 回退兼容）
        $cacheKey = "session_replay_keys_{$session->session_id}";
        $keys = Typed::arr(Cache::get($cacheKey));
        $chunkKey = 'session_replay_chunk_'.md5($session->session_id.'_'.count($keys).'_'.uniqid('', true));
        Cache::put($chunkKey, $events, now()->addDays(Typed::int(config('monit.pixel.replays_retention_days'))));
        $keys[] = $chunkKey;
        Cache::put($cacheKey, $keys, now()->addDays(Typed::int(config('monit.pixel.replays_retention_days'))));
    }

    /**
     * 回放 chunk 持久化（首建或追加）：单事务 + 行锁，保证「读取→合并→写回」原子。
     * $skipped 以引用传出：配额拒收时调用方提前返回（跳过 Cache 缓冲）。
     *
     * @param  array<int|string, mixed>  $events
     */
    protected function persistReplayChunk(VisitorSession $session, array $events, int $chunkSize, bool &$skipped): void
    {
        DB::transaction(function () use ($session, $events, $chunkSize, &$skipped): void {
            $replay = SessionReplay::where('session_id', $session->session_id)
                ->lockForUpdate()
                ->first();

            if (! $replay) {
                // 回放配额（规格 §10.2：sessions_replays_limit；-1 不限、显式配置 0=无此功能）。
                // 判定与 PixelTrackController::doHeatmapCheck 的 replay_enabled 严格一致：
                // 0（落地页明确宣传「该套餐无回放」）或超限时一律拒收——
                // 原实现「>0 才检查」会把 0（禁用）误放行，造成配额绕过。
                // 缺键 ?? -1 = 不限：对齐 sessions_events_limit 的既有模式
                // （custom 用户 plan_settings 不与 plan_defaults 合并，未配置即不加限）。
                $replayLimit = $this->website->user?->getPlanSettings()['sessions_replays_limit'] ?? -1;
                $replayAllowed = ($replayLimit === -1)
                    || ($replayLimit > 0 && $this->website->current_month_sessions_replays < $replayLimit);

                if (! $replayAllowed) {
                    // 标记限额通知（WebsitesLimitNoticeCommand 汇总 → 用户中心展示；
                    // 对齐 sessions_events_limit 超限处理。0=功能禁用，不标记不打扰）
                    if ($replayLimit > 0 && ! $this->website->plan_sessions_replays_limit_notice) {
                        $this->website->forceFill(['plan_sessions_replays_limit_notice' => true])->save();
                    }

                    $this->skip($replayLimit > 0 ? 'replays_limit' : 'replays_disabled');
                    $skipped = true;

                    return;
                }

                // 压缩初始事件数据
                $compressed = gzencode((string) json_encode($events, JSON_UNESCAPED_UNICODE), 9);

                $replay = SessionReplay::create([
                    'session_id' => $session->session_id,
                    'visitor_id' => $session->visitor_id,
                    'website_id' => $this->website->website_id,
                    'user_id' => $this->website->user_id ?? $this->website->user?->user_id,
                    'events' => count($events),
                    'size' => $chunkSize,
                    'datetime' => now(),
                    'is_too_short' => count($events) < 5,
                ]);

                // 用原生 SQL 写入 LONGBLOB data 列（Eloquent 对二进制数据有编码问题）
                if ($compressed !== false) {
                    DB::statement(
                        'UPDATE sessions_replays SET data = ? WHERE replay_id = ?',
                        [$compressed, $replay->replay_id],
                    );
                }

                $this->website->increment('current_month_sessions_replays');
            } else {
                // 追加事件到已有 replay：读取 → 解压 → 合并 → 重新压缩 → 写回
                $existingEvents = [];
                $row = DB::selectOne(
                    'SELECT data FROM sessions_replays WHERE replay_id = ?',
                    [$replay->replay_id],
                );
                if ($row !== null) {
                    $stored = data_get($row, 'data');
                    if (is_string($stored) && $stored !== '') {
                        $decompressed = @gzdecode($stored);
                        if ($decompressed !== false) {
                            $existingEvents = Typed::arr(json_decode($decompressed, true));
                        }
                    }
                }

                // 合并新事件
                $allEvents = array_merge($existingEvents, $events);
                $compressed = gzencode((string) json_encode($allEvents, JSON_UNESCAPED_UNICODE), 9);

                DB::statement(
                    'UPDATE sessions_replays SET data = ?, events = ?, size = ?, last_datetime = ? WHERE replay_id = ?',
                    [$compressed !== false ? $compressed : null, count($allEvents), strlen((string) json_encode($allEvents, JSON_UNESCAPED_UNICODE)), now(), $replay->replay_id],
                );
            }
        });
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

        $data = Typed::arr($this->payload['data'] ?? []);

        // Only store snapshots that contain valid rrweb events (Meta type 4 + FullSnapshot type 2).
        // If rrweb failed to start, the client sends { events: [], viewport: {...} } — skip storing
        // to avoid filling the DB with useless data; click/scroll coordinates still work without a snapshot.
        $events = $data['events'] ?? [];
        $hasMeta = false;
        $hasFull = false;
        if (is_array($events)) {
            foreach ($events as $event) {
                if (is_array($event) && isset($event['type'])) {
                    if (Typed::int($event['type']) === 4) {
                        $hasMeta = true;
                    }
                    if (Typed::int($event['type']) === 2) {
                        $hasFull = true;
                    }
                }
            }
        }

        if ($hasMeta && $hasFull) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            $compressed = gzencode((string) $json, 9);

            // 检查是否已有该设备的 snapshot（可能由 click/scroll 先到达时自动创建的空快照）
            $existingSnapshotId = $heatmap->{"snapshot_id_{$device}"};
            if ($existingSnapshotId) {
                // 更新已有快照的真实 DOM 数据（原生 SQL 写 LONGBLOB，避免 Eloquent 编码问题）
                DB::statement(
                    'UPDATE heatmaps_snapshots SET data = ? WHERE snapshot_id = ?',
                    [$compressed, $existingSnapshotId],
                );
                $heatmap->forceFill([
                    "{$device}_size" => strlen((string) $compressed),
                ])->save();
            } else {
                // 创建新快照（先 Eloquent 创建获取 snapshot_id，再原生 SQL 写 data）
                // 临时填入空对象压缩值以满足 NOT NULL 约束
                $placeholder = gzencode('{}', 9);
                $snapshot = HeatmapSnapshot::create([
                    'heatmap_id' => $heatmap->heatmap_id,
                    'website_id' => $this->website->website_id,
                    'type' => $device,
                    'data' => $placeholder,
                    'date' => now()->toDateString(),
                ]);

                // 原生 SQL 写入真实 LONGBLOB data
                DB::statement(
                    'UPDATE heatmaps_snapshots SET data = ? WHERE snapshot_id = ?',
                    [$compressed, $snapshot->snapshot_id],
                );

                $heatmap->forceFill([
                    "snapshot_id_{$device}" => $snapshot->snapshot_id,
                    "{$device}_size" => strlen((string) $compressed),
                ])->save();
            }
        }
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

        $x = max(0, min(100, Typed::float($this->payload['x_normalized'] ?? 0)));
        $y = max(0, min(100, Typed::float($this->payload['y_normalized'] ?? 0)));
        $count = max(1, min(10, Typed::int($this->payload['count'] ?? 1)));

        HeatmapSnapshotClick::create([
            'website_id' => $this->website->website_id,
            'snapshot_id' => $heatmap->{"snapshot_id_{$device}"},
            'x_normalized' => $x,
            'y_normalized' => $y,
            'count' => $count,
            'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days')))->toDateString(),
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

        $maxScroll = Typed::int(round(max(0, min(100, Typed::int($this->payload['max_scroll'] ?? 0))) / 10)) * 10;

        $uuidBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_session_event_uuid'] ?? null));
        if ($uuidBinary === null) {
            $this->skip('invalid_uuid');

            return;
        }

        HeatmapSnapshotScroll::upsert(
            [[
                'website_id' => $this->website->website_id,
                'snapshot_id' => $heatmap->{"snapshot_id_{$device}"},
                'event_uuid_binary' => $uuidBinary,
                'max_scroll' => $maxScroll,
                'expiration_date' => now()->addDays(Typed::int(config('monit.pixel.events_retention_days')))->toDateString(),
                'last_datetime' => now(),
                'datetime' => now(),
            ]],
            ['website_id', 'snapshot_id', 'event_uuid_binary'],
            ['max_scroll', 'last_datetime']
        );
    }

    /**
     * 查找本网站启用中的热图（payload.heatmap_id）
     */
    protected function findEnabledHeatmap(): ?Heatmap
    {
        $heatmapId = Typed::int($this->payload['heatmap_id'] ?? 0);

        $heatmap = Heatmap::where('website_id', $this->website->website_id)
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
     * 如果 snapshot 不存在则自动创建一个空快照（修复快照未到达时 click/scroll 数据丢失问题）
     *
     * @return string|null desktop|tablet|mobile 或 null（快照未采集）
     */
    protected function currentDeviceColumn(Heatmap $heatmap): ?string
    {
        $device = $this->uaParser->deviceType();
        if (! in_array($device, ['desktop', 'tablet', 'mobile'], true)) {
            $device = 'desktop';
        }

        // snapshot_id 已存在 → 直接返回
        if ($heatmap->{"snapshot_id_{$device}"}) {
            return $device;
        }

        // snapshot_id not present -> auto-create empty snapshot (preserve click/scroll data when snapshot arrives later)
        $emptyCompressed = gzencode('{}', 9);
        $snapshot = HeatmapSnapshot::create([
            'heatmap_id' => $heatmap->heatmap_id,
            'website_id' => $this->website->website_id,
            'type' => $device,
            'data' => $emptyCompressed,
            'date' => now()->toDateString(),
        ]);

        $heatmap->forceFill([
            "snapshot_id_{$device}" => $snapshot->snapshot_id,
            "{$device}_size" => strlen((string) $emptyCompressed),
        ])->save();

        return $device;
    }

    /* ---------------------------------------------------------------------
     | 查找辅助
     --------------------------------------------------------------------- */

    protected function findVisitor(): ?WebsiteVisitor
    {
        $uuidBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_uuid'] ?? null));
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
        $payloadData = $this->payload['data'] ?? [];
        $this->upsertVisitor(Typed::arr($payloadData));

        return $this->findVisitor();
    }

    protected function findSession(WebsiteVisitor $visitor): ?VisitorSession
    {
        $uuidBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_session_uuid'] ?? null));
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
            $timeout = Typed::int(config('monit.pixel.session_timeout'));
            if ($session->date && $session->date->diffInSeconds(now()) <= $timeout) {
                return $session;
            }
        }

        return VisitorSession::create([
            'session_uuid_binary' => $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_session_uuid'] ?? null) ?? Uuid::uuid4()->toString()),
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

        $eventUuidBinary = $this->uuidToBinary(Typed::stringOrNull($this->payload['visitor_session_event_uuid'] ?? null));
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
        // X-Forwarded-For 的信任判定已由 TrustProxies 中间件统一处理
        // （AppServiceProvider::boot 依据 TRUSTED_PROXIES 配置；不可信来源的
        // 伪造头不会影响 ip()）。此处禁止再自行解析原始 XFF 头——否则直连
        // 部署时攻击者仍可伪造访客 IP 污染统计/绕过 IP 级逻辑。
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
     *
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    protected function parseUrlPath(array $data): array
    {
        $url = Typed::string($data['url'] ?? $this->payload['url'] ?? '');
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '/');
        $query = (string) (parse_url($url, PHP_URL_QUERY) ?: '');

        if (! $this->website->query_parameters_tracking_is_enabled) {
            $query = '';
        }

        return [mb_substr($path, 0, 2048), $query];
    }

    /**
     * 解析 referrer 的 host 或 path
     *
     * @param  array<string, mixed>  $data
     */
    protected function parseReferrer(array $data, string $part): ?string
    {
        $referrer = Typed::string($data['referrer'] ?? '');
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

        return $value && is_string($value) ? mb_substr($value, 0, 256) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function parseResolution(array $data): ?string
    {
        $width = Typed::int(data_get($data, 'resolution.width'));
        $height = Typed::int(data_get($data, 'resolution.height'));

        if ($width <= 0 || $height <= 0) {
            return null;
        }

        return $width.'x'.$height;
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterCustomParameters(mixed $parameters): array
    {
        if (! is_array($parameters)) {
            return [];
        }

        $filtered = [];
        foreach (array_slice($parameters, 0, Typed::int(config('monit.pixel.max_custom_parameters'))) as $key => $value) {
            $filtered[mb_substr(Typed::string($key), 0, 64)] = mb_substr(Typed::string($value), 0, 256);
        }

        return $filtered;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function sanitizeEventData(array $data): array
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        return Typed::arr(json_decode(mb_substr(Typed::string($json), 0, 8192), true));
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
