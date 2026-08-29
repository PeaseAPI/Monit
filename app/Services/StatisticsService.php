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

    public function __construct(protected Website $website)
    {
        $this->isLightweight = $website->isLightweight();
    }

    public static function for(Website $website): static
    {
        return new static($website);
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
            $pageviews = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->count();

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
            'country_code', 'device_type', 'os_name', 'browser_name'];

        if (! in_array($dimension, $allowed, true)) {
            return [];
        }

        if ($this->isLightweight) {
            $rows = LightweightEvent::query()
                ->where('website_id', $this->website->website_id)
                ->whereBetween('date', [$this->startDate, $this->endDate])
                ->whereIn('type', ['landing_page', 'pageview'])
                ->groupBy($dimension)
                ->selectRaw("{$dimension} as k, count(*) as total")
                ->orderByDesc('total')
                ->limit($limit)
                ->get();
        } else {
            // 访客维度信息在 websites_visitors 表
            $visitorDimensions = ['country_code', 'device_type', 'os_name', 'browser_name'];

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
                $rows = DB::table('sessions_events')
                    ->where('website_id', $this->website->website_id)
                    ->whereBetween('date', [$this->startDate, $this->endDate])
                    ->whereIn('type', ['landing_page', 'pageview'])
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
}
