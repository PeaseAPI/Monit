<?php

namespace App\Console\Commands;

use App\Models\LightweightEvent;
use App\Models\WebsiteVisitor;
use App\Services\GeoIp;
use Illuminate\Console\Command;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

/**
 * 按当前 GeoIP 库重刷存量地理数据（M31）
 *
 * 背景：接入 ip2region 中文省/市库后，此前的访客/事件行里 city_name/region_name
 * 存的是 db-ip 英文名（Beijing/Hangzhou 等），需要一次性重算为中文。
 *
 * 范围与策略：
 * - websites_visitors：有 IP 的行全量重算，四字段（continent/country/region/city）
 *   采用「新值非空才覆盖」（null 合并保留旧值）——避免 xdb/mmdb 任一缺失时清掉旧数据
 * - lightweight_events：无 IP 列，通过 visitor_uuid 关联访客表取 IP；仅当该 IP
 *   能解析出省/市增益（中文命中）时才更新——事件表体量大，其余行不动
 *
 * 用法：php artisan geoip:refresh-visitors（每月 geoip:update 后跑一次即可，
 * 日常增量数据由 PixelTracker 写入时自动解析）
 */
class GeoipRefreshVisitors extends Command
{
    protected $signature = 'geoip:refresh-visitors {--chunk=1000 : 分块大小}';

    protected $description = '按当前 GeoIP 库（ip2region 中文省市 + db-ip city）重刷存量访客与事件的地理字段';

    public function handle(): int
    {
        $xdbPath = config('services.geoip.ip2region_path');
        $mmdbPath = config('services.geoip.mmdb_path');

        $hasXdb = is_string($xdbPath) && is_file($xdbPath);
        $hasMmdb = is_string($mmdbPath) && is_file($mmdbPath);

        if (! $hasXdb && ! $hasMmdb) {
            $this->error('未找到任何 GeoIP 库（ip2region.xdb / country.mmdb），请先运行 php artisan geoip:update');

            return self::FAILURE;
        }

        $geoIp = new GeoIp;
        $chunk = max(100, (int) $this->option('chunk'));

        $visitors = $this->refreshVisitors($geoIp, $chunk);
        $events = $this->refreshEvents($geoIp, $chunk);

        $this->info("✓ 重刷完成：访客 {$visitors} 行，事件 {$events} 行");

        return self::SUCCESS;
    }

    /**
     * 重刷 websites_visitors（新值非空才覆盖）
     */
    protected function refreshVisitors(GeoIp $geoIp, int $chunk): int
    {
        $count = 0;

        WebsiteVisitor::query()
            ->whereNotNull('ip')
            ->where('ip', '!=', '')
            ->orderBy('visitor_id')
            ->chunkById($chunk, function ($rows) use ($geoIp, &$count): void {
                /** @var iterable<int, WebsiteVisitor> $rows */
                foreach ($rows as $row) {
                    $geo = $geoIp->lookup($row->ip);

                    $next = [
                        'continent_code' => $geo['continent_code'] ?? $row->continent_code,
                        'country_code' => $geo['country_code'] ?? $row->country_code,
                        'region_name' => $geo['region_name'] ?? $row->region_name,
                        'city_name' => $geo['city_name'] ?? $row->city_name,
                    ];

                    $changed = $next['continent_code'] !== $row->continent_code
                        || $next['country_code'] !== $row->country_code
                        || $next['region_name'] !== $row->region_name
                        || $next['city_name'] !== $row->city_name;

                    if ($changed) {
                        $row->forceFill($next)->save();
                    }

                    $count++;
                }
            });

        return $count;
    }

    /**
     * 重刷 lightweight_events（仅省/市有解析增益的 IP——中文命中——才更新）
     */
    protected function refreshEvents(GeoIp $geoIp, int $chunk): int
    {
        $count = 0;

        DB::table('lightweight_events')
            ->join('websites_visitors as v', function (JoinClause $join): void {
                $join->on('v.website_id', '=', 'lightweight_events.website_id')
                    ->on('v.visitor_uuid_binary', '=', 'lightweight_events.visitor_uuid');
            })
            ->whereNotNull('v.ip')
            ->where('v.ip', '!=', '')
            ->select('lightweight_events.event_id as event_id', 'v.ip')
            ->orderBy('lightweight_events.event_id')
            ->chunkById($chunk, function ($rows) use ($geoIp, &$count): void {
                $geoCache = [];
                $pending = [];

                foreach ($rows as $row) {
                    $ip = $row->ip;
                    $eventId = $row->event_id;

                    if (! is_string($ip) || $ip === '' || ! is_numeric($eventId)) {
                        continue;
                    }

                    if (! isset($geoCache[$ip])) {
                        $geoCache[$ip] = $geoIp->lookup($ip);
                    }

                    $geo = $geoCache[$ip];

                    // 仅中文省/市命中（省市至少一项非空）才更新——海外/无增益行保持原样
                    if (($geo['region_name'] ?? null) === null && ($geo['city_name'] ?? null) === null) {
                        continue;
                    }

                    $pending[$ip][] = (int) $eventId;
                }

                // 同 IP 聚合批量更新（单 chunk 内通常不足 1000 条 UPDATE）
                foreach ($pending as $ip => $eventIds) {
                    $geo = $geoCache[$ip];

                    LightweightEvent::query()
                        ->whereIn('event_id', $eventIds)
                        ->update([
                            'continent_code' => $geo['continent_code'],
                            'country_code' => $geo['country_code'],
                            'region_name' => $geo['region_name'],
                            'city_name' => $geo['city_name'],
                        ]);

                    $count += count($eventIds);
                }
            }, 'event_id');

        return $count;
    }
}
