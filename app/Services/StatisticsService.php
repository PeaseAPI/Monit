<?php

namespace App\Services;

use App\Models\LightweightEvent;
use App\Models\SessionEvent;
use App\Models\VisitorSession;
use App\Models\Website;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Monit 统计聚合服务
 * 实现规格书 §5 统计指标（advanced 多表 join / lightweight 单表）
 */
class StatisticsService
{
    protected bool $isLightweight;

    protected Carbon $startDate;

    protected Carbon $endDate;

    /** §5.3 AnalyticsFilters：path 前缀 / 其余精确 */
    protected array $filters = [];

    /** 允许的过滤器维度 */
    public const FILTER_DIMENSIONS = [
        'path', 'referrer_host', 'utm_source', 'utm_medium', 'utm_campaign',
        'country_code', 'continent_code', 'device_type', 'os_name', 'browser_name',
        'browser_language', 'goal_id',
        // M22：对齐原版 AnalyticsFilters 访客维度
        'city_name', 'browser_timezone', 'screen_resolution', 'theme', 'ip',
    ];

    public function __construct(protected Website $website)
    {
        $this->isLightweight = $website->isLightweight();
    }

    public static function for(Website $website): static
    {
        return new static($website);
    }

    /**
     * 设置过滤器（规格 §5.3）：['path' => '/blog', 'country_code' => 'CN']
     * path 与 referrer_host 为前缀匹配，其余精确匹配
     */
    public function filters(array $filters): static
    {
        foreach ($filters as $dimension => $value) {
            if (in_array($dimension, static::FILTER_DIMENSIONS, true) && $value !== null && $value !== '') {
                $this->filters[$dimension] = (string) $value;
            }
        }

        return $this;
    }

    /**
     * 应用过滤器到 events 级查询（sessions_events / lightweight_events）
     * 访客维度（country/device/os/browser/language）通过子查询限定 visitor_id
     */
    protected function applyFilters($query, string $model): void
    {
        $visitorDimensions = ['country_code', 'continent_code', 'device_type', 'os_name', 'browser_name', 'browser_language',
            // M22：原版 AnalyticsFilters 访客维度补齐
            'city_name', 'browser_timezone', 'screen_resolution', 'theme', 'ip'];

        foreach ($this->filters as $dimension => $value) {
            if ($dimension === 'goal_id') {
                // 目标过滤（规格 §5.3）：限定为已转化该目标的访客（仅 Advanced；LW 无访客关联）
                if ($model === 'advanced') {
                    $query->whereIn('visitor_id', function ($q) use ($value) {
                        $q->select('visitor_id')
                            ->from('goals_conversions')
                            ->where('goal_id', (int) $value)
                            ->whereNotNull('visitor_id');
                    });
                }

                continue;
            }

            if (in_array($dimension, $visitorDimensions, true)) {
                if ($model === 'lightweight') {
                    // LW 单表自带部分维度列
                    if (in_array($dimension, ['country_code', 'continent_code', 'device_type', 'os_name', 'browser_name', 'browser_language'], true)) {
                        $query->where($dimension, '=', $value);
                    }
                } else {
                    $query->whereIn('visitor_id', function ($q) use ($dimension, $value) {
                        $q->select('visitor_id')
                            ->from('websites_visitors')
                            ->where('website_id', $this->website->website_id)
                            ->where($dimension, '=', $value);
                    });
                }
            } elseif ($dimension === 'path' || $dimension === 'referrer_host') {
                $query->where($dimension, 'like', $value.'%');
            } else {
                $query->where($dimension, '=', $value);
            }
        }
    }

    public function between(Carbon $start, Carbon $end): static
    {
        $this->startDate = $start->copy()->startOfDay();
        $this->endDate = $end->copy()->endOfDay();

        return $this;
    }

    /**
     * 最近 N 天
     */
    public function lastDays(int $days = 7): static
    {
        return $this->between(now()->subDays($days - 1), now());
    }

    /* ---------------------------------------------------------------------
     | 核心指标
     --------------------------------------------------------------------- */

    /**
     * 概览：PV / UV / 会话 / 跳出率 / 平均停留时长
     */
    public function overview(): array
    {
        if ($this->isLightweight) {
            $lwQuery = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview']);
            $this->applyFilters($lwQuery, 'lightweight');

            $pageviews = (clone $lwQuery)->count();

            // 轻量模式无访客关联：以总量近似
            return [
                'pageviews' => $pageviews, 'visitors' => $pageviews,
                'sessions' => $pageviews, 'bounce_rate' => 0.0, 'avg_duration' => 0,
            ];
        }

        $events = SessionEvent::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('type', ['landing_page', 'pageview']);
        $this->applyFilters($events, 'advanced');

        $pageviews = (clone $events)->count();
        $visitors = (clone $events)->distinct('visitor_id')->count('visitor_id');

        $sessionCount = VisitorSession::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->count();

        // 跳出率：landing_page 事件中 has_bounced=1 的比例（规格书 §5.1）
        $landing = SessionEvent::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('type', 'landing_page');

        $landingCount = (clone $landing)->count();
        $bouncedCount = (clone $landing)->where('has_bounced', true)->count();
        $bounceRate = $landingCount > 0 ? round($bouncedCount / $landingCount * 100, 1) : 0.0;

        // 平均停留时长（秒）：会话最后事件时间 - 会话开始时间 的平均
        $avgDuration = VisitorSession::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('AVG((SELECT MAX(se.date) FROM sessions_events se WHERE se.session_id = visitors_sessions.session_id) - visitors_sessions.date) as avg_duration')
            ->value('avg_duration');

        return [
            'pageviews' => $pageviews,
            'visitors' => $visitors,
            'sessions' => $sessionCount,
            'bounce_rate' => $bounceRate,
            'avg_duration' => $avgDuration ? (int) $avgDuration : 0,
        ];
    }

    /**
     * 实时在线：5 分钟内有事件的独立访客（规格书 §5.1）
     */
    public function realtime(): int
    {
        $since = now()->subMinutes(5);

        if ($this->isLightweight) {
            return LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->where('date', '>=', $since)
                ->count();
        }

        return SessionEvent::query()
            ->where('website_id', $this->website->website_id)
            ->where('date', '>=', $since)
            ->distinct('visitor_id')
            ->count('visitor_id');
    }

    /**
     * 按日趋势（图表数据）
     *
     * @return array<int, array{date: string, pageviews: int, visitors: int}>
     */
    public function dailySeries(): array
    {
        if ($this->isLightweight) {
            $rows = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('day')
                ->selectRaw('date(date) as day, count(*) as pageviews, count(*) as visitors')
                ->get();
        } else {
            $rows = SessionEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('day')
                ->selectRaw('date(date) as day, count(*) as pageviews, count(distinct visitor_id) as visitors')
                ->get();
        }

        $byDay = [];
        foreach ($rows as $row) {
            $byDay[$row->day] = ['pageviews' => (int) $row->pageviews, 'visitors' => (int) $row->visitors];
        }

        // 补全日期空洞
        $series = [];
        for ($date = $this->startDate->copy(); $date <= $this->endDate; $date->addDay()) {
            $key = $date->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'pageviews' => $byDay[$key]['pageviews'] ?? 0,
                'visitors' => $byDay[$key]['visitors'] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * 通用维度分组（路径/来源/UTM/地域/设备/OS/浏览器）
     *
     * @param  string  $dimension  path|referrer_host|utm_source|utm_medium|utm_campaign|country_code|device_type|os_name|browser_name
     * @return array<int, array{key: string, count: int}>
     */
    public function breakdown(string $dimension, int $limit = 10): array
    {
        $allowed = ['path', 'referrer_host', 'utm_source', 'utm_medium', 'utm_campaign',
            'continent_code', 'country_code', 'city_name', 'device_type', 'os_name',
            'browser_name', 'browser_language', 'screen_resolution', 'theme',
            // M22：原版 browser-timezones 页
            'browser_timezone'];

        if (! in_array($dimension, $allowed, true)) {
            return [];
        }

        if ($this->isLightweight) {
            $lwB = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview']);
            $this->applyFilters($lwB, 'lightweight');
            $rows = $lwB
                ->groupBy($dimension)
                ->selectRaw("{$dimension} as k, count(*) as total")
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } else {
            // 访客维度信息在 websites_visitors 表
            $visitorDimensions = ['continent_code', 'country_code', 'city_name', 'device_type', 'os_name', 'browser_name', 'browser_language', 'screen_resolution', 'theme', 'browser_timezone'];

            if (in_array($dimension, $visitorDimensions, true)) {
                $rows = DB::table('sessions_events')
                    ->join('websites_visitors', 'websites_visitors.visitor_id', '=', 'sessions_events.visitor_id')
                    ->where('sessions_events.website_id', $this->website->website_id)
                    ->whereBetween('sessions_events.date', [$this->startDate, $this->endDate])
                    ->whereIn('sessions_events.type', ['landing_page', 'pageview'])
                    ->groupBy("websites_visitors.{$dimension}")
                    ->selectRaw("websites_visitors.{$dimension} as k, count(distinct sessions_events.visitor_id) as total")
                    ->orderByDesc('total')
                    ->limit($limit)
                    ->get();
            } else {
                $evtB = DB::table('sessions_events')
                    ->where('website_id', $this->website->website_id)
                    ->whereBetween('date', [$this->startDate, $this->endDate])
                    ->whereIn('type', ['landing_page', 'pageview']);
                $this->applyFilters($evtB, 'advanced');
                $rows = $evtB
                    ->groupBy($dimension)
                    ->selectRaw("{$dimension} as k, count(*) as total")
                    ->orderByDesc('total')
                    ->limit($limit)
                    ->get();
            }
        }

        return collect($rows)->map(fn ($row) => [
                        'key' => (string) ($row->k ?? __('stats.unknown')),
            'count' => (int) $row->total,
        ])->all();
    }

    /**
     * 来源 + UTM 组合分析（包含 utm_* 分组）
     *
     * @return array<int, array{key: string, count: int, utm_source?: string, utm_medium?: string, utm_campaign?: string}>
     */
    public function breakdownWithUtm(string $dimension, int $limit = 50): array
    {
        if ($dimension !== 'referrer_host') {
            return $this->breakdown($dimension, $limit);
        }

        if ($this->isLightweight) {
            $rows = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('referrer_host', 'utm_source', 'utm_medium', 'utm_campaign')
                ->selectRaw('referrer_host as k, utm_source, utm_medium, utm_campaign, count(*) as total')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } else {
            $rows = SessionEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('referrer_host', 'utm_source', 'utm_medium', 'utm_campaign')
                ->selectRaw('referrer_host as k, utm_source, utm_medium, utm_campaign, count(*) as total')
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        }

        return collect($rows)->map(fn ($row) => [
                        'key' => (string) ($row->k ?: __('stats.direct_access')),
            'count' => (int) $row->total,
            'utm_source' => $row->utm_source,
            'utm_medium' => $row->utm_medium,
            'utm_campaign' => $row->utm_campaign,
        ])->all();
    }

    /**
     * 顶级访客列表（按访客维度聚合）
     *
     * @return array<int, array{visitor_id: int, visitor_uuid: string, country_code: string, device_type: string, os_name: string, browser_name: string, total_events: int, last_date: string}>
     */
    public function topVisitors(int $limit = 50): array
    {
        if ($this->isLightweight) {
            return [];
        }

                $hexFunc = DB::getDriverName() === 'sqlite'
            ? "LOWER(HEX(websites_visitors.visitor_uuid_binary))"
            : "HEX(websites_visitors.visitor_uuid_binary)";

        $rows = DB::table('websites_visitors')
            ->join('sessions_events', 'websites_visitors.visitor_id', '=', 'sessions_events.visitor_id')
            ->where('websites_visitors.website_id', $this->website->website_id)
            ->whereBetween('sessions_events.date', [$this->startDate, $this->endDate])
            ->groupBy('websites_visitors.visitor_id')
            ->selectRaw("websites_visitors.visitor_id, {$hexFunc} as visitor_uuid, websites_visitors.country_code, websites_visitors.device_type, websites_visitors.os_name, websites_visitors.browser_name, COUNT(sessions_events.event_id) as total_events, MAX(sessions_events.date) as last_date")
            ->orderByDesc('total_events')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => [
            'visitor_id' => (int) $row->visitor_id,
            'visitor_uuid' => $row->visitor_uuid,
                        'country_code' => $row->country_code ?? __('stats.unknown'),
            'device_type' => $row->device_type ?? __('stats.unknown'),
            'os_name' => $row->os_name ?? __('stats.unknown'),
            'browser_name' => $row->browser_name ?? __('stats.unknown'),
            'total_events' => (int) $row->total_events,
            'last_date' => $row->last_date,
        ])->all();
    }

    /**
     * 目标转化统计
     *
     * @return array<int, array{goal_id: int, goal_key: string, goal_name: string, conversions: int}>
     */
    public function goalsConversions(): array
    {
        $goals = $this->website->goals()
            ->where('is_enabled', true)
            ->get();

        $conversions = [];

        foreach ($goals as $goal) {
            $count = GoalConversion::query()
                ->where('website_id', $this->website->website_id)
                ->where('goal_id', $goal->goal_id)
                ->whereBetween('datetime', [$this->startDate, $this->endDate])
                ->count();

            $conversions[] = [
                'goal_id' => $goal->goal_id,
                'goal_key' => $goal->key,
                'goal_name' => $goal->name,
                'conversions' => $count,
            ];
        }

        return $conversions;
    }

    /**
     * UTM 来源分析（独立聚合）
     *
     * @return array<int, array{key: string, source: string, medium: string, campaign: string, count: int}>
     */
    public function utmAnalysis(): array
    {
        $dimensions = ['utm_source', 'utm_medium', 'utm_campaign'];
        $results = [];

        foreach ($dimensions as $dim) {
            $rows = $this->breakdown($dim, 20);
            foreach ($rows as $row) {
                                if (! empty($row['key']) && $row['key'] !== __('stats.unknown')) {
                    $results[] = [
                        'key' => $row['key'],
                        'type' => $dim,
                        'count' => $row['count'],
                    ];
                }
            }
        }

        usort($results, fn ($a, $b) => $b['count'] - $a['count']);

        return array_slice($results, 0, 30);
    }

    /* ---------------------------------------------------------------------
     | M21 GA/CNZZ 对标扩展：搜索引擎/社交识别表
     --------------------------------------------------------------------- */

    /** 搜索引擎域名后缀 => 搜索词 query 参数名（按序尝试） */
    protected const SEARCH_ENGINES = [
        'baidu.com' => ['wd', 'word', 'kw'],
        'google.com' => ['q'],
        'google.com.hk' => ['q'],
        'bing.com' => ['q'],
        'sogou.com' => ['query', 'keyword'],
        'so.com' => ['q'],
        '360.cn' => ['q'],
        'sm.cn' => ['q'],
        'yandex.com' => ['text'],
        'yahoo.com' => ['p', 'q'],
        'duckduckgo.com' => ['q'],
    ];

    /** 社交平台域名后缀（渠道分组 social） */
    protected const SOCIAL_HOSTS = [
        'weibo.com', 'zhihu.com', 'douyin.com', 'kuaishou.com', 'xiaohongshu.com',
        'bilibili.com', 'tieba.baidu.com', 'mp.weixin.qq.com', 'x.com', 'twitter.com',
        't.co', 'facebook.com', 'instagram.com', 'linkedin.com', 'pinterest.com',
        'reddit.com', 'youtube.com', 'vk.com', 't.me', 'telegram.me', 'douban.com',
    ];

    /** 判定 host 是否属于搜索引擎；返回参数名列表或 null */
    protected function searchEngineParams(?string $host): ?array
    {
        if (! $host) {
            return null;
        }

        $host = strtolower($host);

        foreach (static::SEARCH_ENGINES as $suffix => $params) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return $params;
            }
        }

        return null;
    }

    /** 判定 host 是否属于社交平台 */
    protected function isSocialHost(?string $host): bool
    {
        if (! $host) {
            return false;
        }

        $host = strtolower($host);

        foreach (static::SOCIAL_HOSTS as $suffix) {
            if ($host === $suffix || str_ends_with($host, '.'.$suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * M21 时段分析（CNZZ「时段分析」/GA「按小时」）：0-23 时 PV 与访客分布
     *
     * @return array<int, array{hour: int, label: string, pageviews: int, visitors: int}>
     */
    public function hourlySeries(): array
    {
        $hourExpr = DB::getDriverName() === 'sqlite'
            ? "strftime('%H', date)"
            : "date_format(date, '%H')";

        if ($this->isLightweight) {
            $rows = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('h')
                ->selectRaw("{$hourExpr} as h, count(*) as pageviews, count(*) as visitors")
                ->get();
        } else {
            $rows = SessionEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('h')
                ->selectRaw("{$hourExpr} as h, count(*) as pageviews, count(distinct visitor_id) as visitors")
                ->get();
        }

        $byHour = [];
        foreach ($rows as $row) {
            $byHour[(int) $row->h] = ['pageviews' => (int) $row->pageviews, 'visitors' => (int) $row->visitors];
        }

        $series = [];
        for ($h = 0; $h <= 23; $h++) {
            $series[] = [
                'hour' => $h,
                'label' => sprintf('%02d:00', $h),
                'pageviews' => $byHour[$h]['pageviews'] ?? 0,
                'visitors' => $byHour[$h]['visitors'] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * M21 入口页（GA「着陆页」）：每个会话首个事件的 path
     *
     * @return array<int, array{key: string, count: int}>
     */
    public function landingPages(int $limit = 10): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('type', 'landing_page')
            ->groupBy('path')
            ->selectRaw('path as k, count(*) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => ['key' => (string) $row->k, 'count' => (int) $row->total])->all();
    }

    /**
     * M21 离开页（GA「退出页」）：每个会话最后一个事件的 path
     *
     * @return array<int, array{key: string, count: int}>
     */
    public function exitPages(int $limit = 10): array
    {
        if ($this->isLightweight) {
            return [];
        }

        $rows = DB::table('sessions_events as e')
            ->join(DB::raw('(select session_id, max(event_id) as max_id from sessions_events where website_id = '.(int) $this->website->website_id.' and date between ? and ? group by session_id) as m'),
                'e.event_id', '=', 'm.max_id')
            ->addBinding([$this->startDate, $this->endDate], 'join')
            ->groupBy('e.path')
            ->selectRaw('e.path as k, count(*) as total')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => ['key' => (string) $row->k, 'count' => (int) $row->total])->all();
    }

    /**
     * M21 搜索词（CNZZ「搜索词」/GA「自然搜索关键词」）：从搜索引擎 referrer 解析
     *
     * @return array<int, array{key: string, engines: string, count: int}>
     */
    public function searchTerms(int $limit = 10): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('type', 'landing_page')
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->groupBy('referrer_host', 'referrer_path')
            ->selectRaw('referrer_host, referrer_path, count(*) as total')
            ->orderByDesc('total')
            ->limit(500)
            ->get();

        $merged = [];
        foreach ($rows as $row) {
            $params = $this->searchEngineParams($row->referrer_host);
            if (! $params) {
                continue;
            }

            $term = $this->extractSearchTerm($row->referrer_path, $params);
            if ($term === null || $term === '') {
                continue;
            }

            if (! isset($merged[$term])) {
                $merged[$term] = ['term' => $term, 'engines' => [], 'count' => 0];
            }
            $merged[$term]['engines'][(string) $row->referrer_host] = true;
            $merged[$term]['count'] += (int) $row->total;
        }

        $items = array_values($merged);
        usort($items, fn ($a, $b) => $b['count'] - $a['count']);

        return array_slice(array_map(fn ($m) => [
            'key' => $m['term'],
            'engines' => implode(' / ', array_keys($m['engines'])),
            'count' => $m['count'],
        ], $items), 0, $limit);
    }

    /** 从 referrer_path 解析搜索词；解析失败返回 null */
    protected function extractSearchTerm(?string $path, array $paramNames): ?string
    {
        if (! $path || ! str_contains($path, '?')) {
            return null;
        }

        parse_str(substr($path, (int) strpos($path, '?') + 1), $query);

        foreach ($paramNames as $name) {
            if (! empty($query[$name]) && is_string($query[$name])) {
                return mb_substr(trim($query[$name]), 0, 120);
            }
        }

        return null;
    }

    /**
     * M21 渠道分组（GA「默认渠道分组」）：direct / organic / social / referral / campaign
     *
     * @return array<string, int>
     */
    public function channels(): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->where('type', 'landing_page')
            ->groupBy('referrer_host', 'utm_source', 'utm_medium')
            ->selectRaw('referrer_host, utm_source, utm_medium, count(*) as total')
            ->get();

        $result = ['direct' => 0, 'organic' => 0, 'social' => 0, 'referral' => 0, 'campaign' => 0];
        $selfHost = strtolower((string) ($this->website->host ?? $this->website->domain ?? ''));

        foreach ($rows as $row) {
            $count = (int) $row->total;
            $host = $row->referrer_host ? strtolower((string) $row->referrer_host) : '';
            $hasUtm = ($row->utm_source && $row->utm_source !== '') || ($row->utm_medium && $row->utm_medium !== '');

            if ($hasUtm) {
                $result['campaign'] += $count;
            } elseif ($host === '' || ($selfHost !== '' && $host === $selfHost)) {
                $result['direct'] += $count;
            } elseif ($this->searchEngineParams($host)) {
                $result['organic'] += $count;
            } elseif ($this->isSocialHost($host)) {
                $result['social'] += $count;
            } else {
                $result['referral'] += $count;
            }
        }

        return $result;
    }

    /**
     * M21 忠诚度分析（CNZZ「忠诚度」/GA「新访与回访 + 行为深度」）
     * 新老访客、访问频次、访问深度、访问时长四组分布
     *
     * @return array{new_visitors: int, returning_visitors: int, frequency: array<int, array{key: string, count: int}>, depth: array<int, array{key: string, count: int}>, duration: array<int, array{key: string, count: int}>}
     */
    public function loyalty(): array
    {
        if ($this->isLightweight) {
            return ['new_visitors' => 0, 'returning_visitors' => 0, 'frequency' => [], 'depth' => [], 'duration' => []];
        }

        // 访问频次：窗口内每访客会话数（1 次 ≈ 新访客）
        $freqRows = VisitorSession::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->groupBy('visitor_id')
            ->selectRaw('count(*) as sessions')
            ->get();

        $newVisitors = 0;
        $returning = 0;
        $freqBuckets = ['1' => 0, '2' => 0, '3-4' => 0, '5-9' => 0, '10+' => 0];

        foreach ($freqRows as $row) {
            $n = (int) $row->sessions;
            $n === 1 ? $newVisitors++ : $returning++;

            if ($n === 1) { $freqBuckets['1']++; }
            elseif ($n === 2) { $freqBuckets['2']++; }
            elseif ($n <= 4) { $freqBuckets['3-4']++; }
            elseif ($n <= 9) { $freqBuckets['5-9']++; }
            else { $freqBuckets['10+']++; }
        }

        // 会话集合：深度（total_events）+ 时长（最后事件时间 - 会话开始）
        $sessions = VisitorSession::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->selectRaw('session_id, date, total_events')
            ->get();

        $lastBySession = DB::table('sessions_events')
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->groupBy('session_id')
            ->selectRaw('session_id, max(date) as last_date')
            ->get()
            ->pluck('last_date', 'session_id')
            ->all();

        $depthBuckets = ['1' => 0, '2-3' => 0, '4-10' => 0, '11-30' => 0, '30+' => 0];
        $durationBuckets = ['0-10s' => 0, '11-30s' => 0, '31-60s' => 0, '1-3m' => 0, '3m+' => 0];

        foreach ($sessions as $session) {
            $events = (int) $session->total_events;
            if ($events <= 1) { $depthBuckets['1']++; }
            elseif ($events <= 3) { $depthBuckets['2-3']++; }
            elseif ($events <= 10) { $depthBuckets['4-10']++; }
            elseif ($events <= 30) { $depthBuckets['11-30']++; }
            else { $depthBuckets['30+']++; }

            if ($last = ($lastBySession[$session->session_id] ?? null)) {
                $seconds = max(0, strtotime((string) $last) - strtotime((string) $session->date));

                if ($seconds <= 10) { $durationBuckets['0-10s']++; }
                elseif ($seconds <= 30) { $durationBuckets['11-30s']++; }
                elseif ($seconds <= 60) { $durationBuckets['31-60s']++; }
                elseif ($seconds <= 180) { $durationBuckets['1-3m']++; }
                else { $durationBuckets['3m+']++; }
            }
        }

        $toItems = fn (array $buckets) => array_map(
            fn ($k, $v) => ['key' => (string) $k, 'count' => $v],
            array_keys($buckets),
            $buckets
        );

        return [
            'new_visitors' => $newVisitors,
            'returning_visitors' => $returning,
            'frequency' => $toItems($freqBuckets),
            'depth' => $toItems($depthBuckets),
            'duration' => $toItems($durationBuckets),
        ];
    }

    /* ---------------------------------------------------------------------
     | M22 原版对齐扩展：星期分布 / 引荐分类 / 钻取（规格书 §5.5）
     --------------------------------------------------------------------- */

    /**
     * M22 星期分布（原版 weekdays 页）：周一至周日 PV 与访客分布
     * 兼容 sqlite（strftime %w：0=周日）与 mysql（DAYOFWEEK：1=周日）→ 统一为 0=周日
     *
     * @return array<int, array{dow: int, label: string, pageviews: int, visitors: int}>
     */
    public function weekdaySeries(): array
    {
        $dowExpr = DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%w', date) AS INTEGER)"
            : '(DAYOFWEEK(date) - 1)';

        if ($this->isLightweight) {
            $rows = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('dow')
                ->selectRaw("{$dowExpr} as dow, count(*) as pageviews, count(*) as visitors")
                ->get();
        } else {
            $rows = SessionEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy('dow')
                ->selectRaw("{$dowExpr} as dow, count(*) as pageviews, count(distinct visitor_id) as visitors")
                ->get();
        }

        $byDow = [];
        foreach ($rows as $row) {
            $srcDow = (int) $row->dow;          // 源：0=周日（sqlite %w / mysql DAYOFWEEK-1 一致）
            $iso = $srcDow === 0 ? 7 : $srcDow;  // ISO：1=周一…7=周日
            $byDow[$iso] = ['pageviews' => (int) $row->pageviews, 'visitors' => (int) $row->visitors];
        }

        // ISO 顺序：周一(1)…周日(7)
        $labels = [1 => 'stats.weekday_mon', 2 => 'stats.weekday_tue', 3 => 'stats.weekday_wed',
            4 => 'stats.weekday_thu', 5 => 'stats.weekday_fri', 6 => 'stats.weekday_sat', 7 => 'stats.weekday_sun'];

        $series = [];
        foreach ($labels as $iso => $labelKey) {
            $data = $byDow[$iso] ?? ['pageviews' => 0, 'visitors' => 0];
            $series[] = [
                'dow' => $iso,
                'label' => __($labelKey),
                'pageviews' => $data['pageviews'],
                'visitors' => $data['visitors'],
            ];
        }

        return $series;
    }

    /** AI 引荐域名归一表（原版 ai_referrers，扩展 gemini）：子域/别名 → 规范域 */
    protected const AI_HOSTS = [
        'chat.openai.com' => 'openai.com',
        'openai.com' => 'openai.com',
        'chatgpt.com' => 'openai.com',
        'claude.ai' => 'claude.ai',
        'perplexity.ai' => 'perplexity.ai',
        'www.perplexity.ai' => 'perplexity.ai',
        'copilot.microsoft.com' => 'copilot.microsoft.com',
        'gemini.google.com' => 'gemini.google.com',
    ];

    /** 社交域名归一表（原版 social_media_referrers CASE 映射，子域归一 + 国内补充） */
    protected const SOCIAL_HOST_MAP = [
        'l.threads.com' => 'threads.com',
        'l.facebook.com' => 'facebook.com', 'lm.facebook.com' => 'facebook.com',
        'm.facebook.com' => 'facebook.com', 'www.facebook.com' => 'facebook.com',
        'staticxx.facebook.com' => 'facebook.com',
        'l.instagram.com' => 'instagram.com', 'www.instagram.com' => 'instagram.com',
        'www.pinterest.com' => 'pinterest.com',
        't.co' => 'x.com', 'twitter.com' => 'x.com',
        'www.youtube.com' => 'youtube.com', 'm.youtube.com' => 'youtube.com', 'youtube.com' => 'youtube.com',
        'www.tiktok.com' => 'tiktok.com', 'm.tiktok.com' => 'tiktok.com',
        'www.reddit.com' => 'reddit.com', 'reddit.com' => 'reddit.com',
        'www.linkedin.com' => 'linkedin.com', 'linkedin.com' => 'linkedin.com',
        'story.snapchat.com' => 'snapchat.com', 'www.snapchat.com' => 'snapchat.com',
        't.me' => 'telegram.org', 'telegram.me' => 'telegram.org', 'web.telegram.org' => 'telegram.org',
        'weibo.com' => 'weibo.com', 'www.weibo.com' => 'weibo.com',
        'zhihu.com' => 'zhihu.com', 'www.zhihu.com' => 'zhihu.com',
        'douyin.com' => 'douyin.com', 'www.douyin.com' => 'douyin.com',
        'bilibili.com' => 'bilibili.com', 'www.bilibili.com' => 'bilibili.com',
        'xiaohongshu.com' => 'xiaohongshu.com',
        'mp.weixin.qq.com' => 'mp.weixin.qq.com',
    ];

    /** 搜索引擎域名归一表（原版 search_engines_referrers CASE 映射） */
    protected const SEARCH_HOST_MAP = [
        'www.bing.com' => 'bing.com', 'bing.com' => 'bing.com',
        'www.baidu.com' => 'baidu.com', 'baidu.com' => 'baidu.com',
        'yandex.com' => 'yandex.com', 'www.yandex.com' => 'yandex.com',
        'sogou.com' => 'sogou.com', 'www.sogou.com' => 'sogou.com',
        'so.com' => 'so.com', 'www.so.com' => 'so.com',
        'duckduckgo.com' => 'duckduckgo.com',
    ];

    /**
     * M22 引荐分类榜（原版 social_media/search_engines/ai_referrers 三页合一，规格书 §5.5）
     *
     * @return array{social: array<int, array{key: string, count: int}>, search: array<int, array{key: string, count: int}>, ai: array<int, array{key: string, count: int}>}
     */
    public function referralCategories(int $limit = 30): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('type', ['landing_page', 'pageview'])
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->groupBy('referrer_host')
            ->selectRaw('referrer_host as k, count(*) as total')
            ->orderByDesc('total')
            ->limit(500)
            ->get();

        $social = $search = $ai = [];

        foreach ($rows as $row) {
            $host = strtolower((string) $row->k);
            $count = (int) $row->total;

            // AI 引荐（openai/claude/perplexity/copilot/gemini）
            if (isset(static::AI_HOSTS[$host])) {
                $canonical = static::AI_HOSTS[$host];
                $ai[$canonical] = ($ai[$canonical] ?? 0) + $count;
                continue;
            }

            // 社交：精确映射 → SOCIAL_HOSTS 后缀匹配
            if (isset(static::SOCIAL_HOST_MAP[$host])) {
                $canonical = static::SOCIAL_HOST_MAP[$host];
                $social[$canonical] = ($social[$canonical] ?? 0) + $count;
                continue;
            }
            foreach (static::SOCIAL_HOSTS as $suffix) {
                if (str_ends_with($host, '.'.$suffix)) {
                    $social[$suffix] = ($social[$suffix] ?? 0) + $count;
                    continue 2;
                }
            }

            // 搜索引擎：精确映射 → google/yahoo/baidu TLD 通配
            if (isset(static::SEARCH_HOST_MAP[$host])) {
                $canonical = static::SEARCH_HOST_MAP[$host];
                $search[$canonical] = ($search[$canonical] ?? 0) + $count;
                continue;
            }
            if (preg_match('/^(?:www\.)?google\.[a-z.]+$/', $host)) {
                $search['google.com'] = ($search['google.com'] ?? 0) + $count;
                continue;
            }
            if (str_ends_with($host, '.yahoo.com')) {
                $search['yahoo.com'] = ($search['yahoo.com'] ?? 0) + $count;
                continue;
            }
            if (preg_match('/^(?:m\.)?baidu\.[a-z.]+$/', $host)) {
                $search['baidu.com'] = ($search['baidu.com'] ?? 0) + $count;
            }
        }

        $toItems = function (array $map) use ($limit) {
            arsort($map);

            return array_slice(array_map(
                fn ($k, $v) => ['key' => (string) $k, 'count' => (int) $v],
                array_keys($map),
                $map
            ), 0, $limit);
        };

        return [
            'social' => $toItems($social),
            'search' => $toItems($search),
            'ai' => $toItems($ai),
        ];
    }

    /**
     * M22 引荐路径钻取（原版 referrer_paths_modal）：指定引荐主机下按路径聚合
     *
     * @return array<int, array{key: string, count: int}>
     */
    public function referrerPaths(string $host, int $limit = 50): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('type', ['landing_page', 'pageview'])
            ->where('referrer_host', $host)
            ->groupBy('referrer_path')
            ->selectRaw("COALESCE(NULLIF(referrer_path, ''), '/') as k, count(*) as total")
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => ['key' => (string) $row->k, 'count' => (int) $row->total])->all();
    }

    /**
     * M22 UTM 钻取（原版 utms_medium_campaign_modal）：指定 utm_source 下 medium×campaign 聚合
     *
     * @return array<int, array{key: string, medium: string, campaign: string, count: int}>
     */
    public function utmDrilldown(string $source, int $limit = 50): array
    {
        $base = $this->isLightweight ? LightweightEvent::query() : SessionEvent::query();

        $rows = $base
            ->where('website_id', $this->website->website_id)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->whereIn('type', ['landing_page', 'pageview'])
            ->where('utm_source', $source)
            ->groupBy('utm_medium', 'utm_campaign')
            ->selectRaw("COALESCE(NULLIF(utm_medium, ''), '—') as m, COALESCE(NULLIF(utm_campaign, ''), '—') as c, count(*) as total")
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => [
            'key' => $row->m.' × '.$row->c,
            'medium' => (string) $row->m,
            'campaign' => (string) $row->c,
            'count' => (int) $row->total,
        ])->all();
    }

    /**
     * M22 出站路径钻取（原版 outbound_clicks_paths_modal）：指定出站主机下按路径聚合
     *
     * @return array<int, array{key: string, count: int}>
     */
    public function outboundClickPaths(string $host, int $limit = 50): array
    {
        $rows = \App\Models\OutboundClick::query()
            ->where('website_id', $this->website->website_id)
            ->whereBetween('datetime', [$this->startDate, $this->endDate])
            ->where('host', $host)
            ->groupBy('path')
            ->selectRaw("COALESCE(NULLIF(path, ''), '/') as k, count(*) as total")
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        return collect($rows)->map(fn ($row) => ['key' => (string) $row->k, 'count' => (int) $row->total])->all();
    }
}
