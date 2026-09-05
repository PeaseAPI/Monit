<?php

namespace Database\Seeders;

use App\Models\Heatmap;
use App\Models\HeatmapSnapshot;
use App\Models\HeatmapSnapshotClick;
use App\Models\HeatmapSnapshotScroll;
use App\Models\SessionReplay;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\Uuid;

class DemoHeatmapReplaySeeder extends Seeder
{
    public function run(): void
    {
        $website = Website::first();
        if (! $website) {
            $this->command->warn('No website found.');
            return;
        }
        $userId = $website->user_id;

        // ── Heatmaps ──────────────────────────────────────────
        foreach (['/', '/about', '/contact'] as $path) {
            if (Heatmap::where('website_id', $website->website_id)->where('path', $path)->exists()) {
                continue;
            }

            $heatmap = Heatmap::create([
                'website_id' => $website->website_id,
                'user_id'    => $userId,
                'path'       => $path,
                'name'       => $path === '/' ? 'Homepage' : ltrim($path, '/'),
                'is_enabled' => true,
                'datetime'   => now(),
            ]);

            $snap = HeatmapSnapshot::create([
                'heatmap_id' => $heatmap->heatmap_id,
                'website_id' => $website->website_id,
                'type'       => 'desktop',
                'data'       => gzencode('{}', 9),
                'date'       => now()->toDateString(),
            ]);

            $heatmap->update([
                'snapshot_id_desktop' => $snap->snapshot_id,
                'desktop_size'        => strlen(gzencode('{}', 9)),
            ]);

            // Click data
            for ($i = 0; $i < 30; $i++) {
                HeatmapSnapshotClick::create([
                    'website_id'      => $website->website_id,
                    'snapshot_id'     => $snap->snapshot_id,
                    'x_normalized'    => round(mt_rand(5, 95) + mt_rand(0, 100) / 100, 2),
                    'y_normalized'    => round(mt_rand(5, 95) + mt_rand(0, 100) / 100, 2),
                    'count'           => mt_rand(1, 5),
                    'expiration_date' => now()->addDays(90)->toDateString(),
                    'datetime'        => now()->subHours(mt_rand(1, 72)),
                ]);
            }

            // Scroll data
            foreach ([10, 25, 50, 75, 90, 100] as $pct) {
                for ($j = 0; $j < mt_rand(2, 8); $j++) {
                    HeatmapSnapshotScroll::create([
                        'website_id'        => $website->website_id,
                        'snapshot_id'       => $snap->snapshot_id,
                        'event_uuid_binary' => Uuid::uuid4()->getBytes(),
                        'max_scroll'        => $pct,
                        'expiration_date'   => now()->addDays(90)->toDateString(),
                        'last_datetime'     => now()->subHours(mt_rand(1, 72)),
                        'datetime'          => now()->subHours(mt_rand(1, 72)),
                    ]);
                }
            }
        }
        // ── Session Replays ───────────────────────────────────
        for ($r = 0; $r < 5; $r++) {
            $visitor = WebsiteVisitor::create([
                'website_id'           => $website->website_id,
                'visitor_uuid_binary'  => Uuid::uuid4()->getBytes(),
                'ip'                   => long2ip(mt_rand(ip2long('1.0.0.0'), ip2long('223.255.255.255'))),
                'continent_code'       => 'AS',
                'country_code'         => 'CN',
                'city_name'            => ['北京', '上海', '广州', '深圳', '杭州'][mt_rand(0, 4)],
                'os_name'              => ['Windows', 'macOS', 'Linux'][mt_rand(0, 2)],
                'browser_name'         => ['Chrome', 'Firefox', 'Safari'][mt_rand(0, 2)],
                'device_type'          => 'desktop',
                'date'                 => now()->subHours(mt_rand(1, 72)),
                'last_date'            => now(),
            ]);

            $session = VisitorSession::create([
                'session_uuid_binary' => Uuid::uuid4()->getBytes(),
                'visitor_id'          => $visitor->visitor_id,
                'website_id'          => $website->website_id,
                'date'                => now()->subHours(mt_rand(1, 72)),
                'total_events'        => mt_rand(5, 30),
            ]);

            SessionReplay::create([
                'session_id'      => $session->session_id,
                'visitor_id'      => $visitor->visitor_id,
                'website_id'      => $website->website_id,
                'user_id'         => $userId,
                'events'          => mt_rand(10, 50),
                'size'            => mt_rand(50000, 500000),
                'is_offloaded'    => false,
                'is_too_short'    => false,
                'datetime'        => now()->subHours(mt_rand(1, 72)),
                'last_datetime'   => now()->subHours(mt_rand(0, 2)),
                'expiration_date' => now()->addDays(30)->toDateString(),
            ]);

            $events = $this->generateRrwebEvents();
            $key = 'session_replay_chunk_' . md5($session->session_id . '_0_' . uniqid('', true));
            Cache::put($key, $events, now()->addDays(30));
            Cache::put("session_replay_keys_{$session->session_id}", [$key], now()->addDays(30));
        }

        $this->command->info('Demo data created!');
    }

    protected function generateRrwebEvents(): array
    {
        $ts = now()->subMinutes(5)->getPreciseTimestamp(3);

        $events = [];
        $events[] = [
            'type' => 4,
            'data' => ['href' => 'https://example.com/', 'width' => 1920, 'height' => 1080],
            'timestamp' => $ts,
        ];
        $events[] = [
            'type' => 2,
            'data' => [
                'node' => [
                    'type' => 0,
                    'childNodes' => [[
                        'type' => 1, 'name' => 'html', 'childNodes' => [
                            ['type' => 1, 'name' => 'head'],
                            ['type' => 1, 'name' => 'body', 'childNodes' => [[
                                'type' => 1, 'name' => 'h1', 'childNodes' => [
                                    ['type' => 3, 'textContent' => 'Welcome'],
                                ],
                            ]]],
                        ],
                    ]],
                ],
            ],
            'timestamp' => $ts + 100,
        ];

        for ($i = 0; $i < 10; $i++) {
            $events[] = [
                'type' => 3,
                'data' => [
                    'source' => 1,
                    'positions' => [['x' => mt_rand(100, 1800), 'y' => mt_rand(100, 900), 'id' => 1, 'timeOffset' => $i * 500]],
                ],
                'timestamp' => $ts + $i * 500,
            ];
            if ($i % 3 === 0) {
                $events[] = [
                    'type' => 3,
                    'data' => ['source' => 2, 'type' => 6, 'id' => 1, 'x' => mt_rand(100, 1800), 'y' => mt_rand(100, 900)],
                    'timestamp' => $ts + $i * 500 + 250,
                ];
            }
        }

        return $events;
    }
}
