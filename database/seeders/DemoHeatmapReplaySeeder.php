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
        if ($website === null) {
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
                'path' => $path,
                'name' => $path === '/' ? 'Homepage' : ltrim($path, '/'),
                'is_enabled' => true,
                'datetime' => now(),
            ]);

            $snapshotEvents = $this->generateSnapshotEvents($path === '/' ? 'Homepage' : ltrim($path, '/'));
            $snap = HeatmapSnapshot::create([
                'heatmap_id' => $heatmap->heatmap_id,
                'website_id' => $website->website_id,
                'type' => 'desktop',
                'data' => gzencode((string) json_encode(['events' => $snapshotEvents, 'viewport' => ['width' => 1920, 'height' => 1080]]), 9),
                'date' => now()->toDateString(),
            ]);

            $compressed = gzencode((string) json_encode(['events' => $snapshotEvents, 'viewport' => ['width' => 1920, 'height' => 1080]]), 9);
            $heatmap->update([
                'snapshot_id_desktop' => $snap->snapshot_id,
                'desktop_size' => $compressed === false ? 0 : strlen($compressed),
            ]);

            // Click data
            for ($i = 0; $i < 30; $i++) {
                HeatmapSnapshotClick::create([
                    'website_id' => $website->website_id,
                    'snapshot_id' => $snap->snapshot_id,
                    'x_normalized' => round(mt_rand(5, 95) + mt_rand(0, 100) / 100, 2),
                    'y_normalized' => round(mt_rand(5, 95) + mt_rand(0, 100) / 100, 2),
                    'count' => mt_rand(1, 5),
                    'expiration_date' => now()->addDays(90)->toDateString(),
                    'datetime' => now()->subHours(mt_rand(1, 72)),
                ]);
            }

            // Scroll data
            foreach ([10, 25, 50, 75, 90, 100] as $pct) {
                for ($j = 0; $j < mt_rand(2, 8); $j++) {
                    HeatmapSnapshotScroll::create([
                        'website_id' => $website->website_id,
                        'snapshot_id' => $snap->snapshot_id,
                        'event_uuid_binary' => Uuid::uuid4()->getBytes(),
                        'max_scroll' => $pct,
                        'expiration_date' => now()->addDays(90)->toDateString(),
                        'last_datetime' => now()->subHours(mt_rand(1, 72)),
                        'datetime' => now()->subHours(mt_rand(1, 72)),
                    ]);
                }
            }
        }
        // ── Session Replays ───────────────────────────────────
        for ($r = 0; $r < 5; $r++) {
            $visitor = WebsiteVisitor::create([
                'website_id' => $website->website_id,
                'visitor_uuid_binary' => Uuid::uuid4()->getBytes(),
                'ip' => long2ip(mt_rand((int) ip2long('1.0.0.0'), (int) ip2long('223.255.255.255'))),
                'continent_code' => 'AS',
                'country_code' => 'CN',
                'city_name' => ['北京', '上海', '广州', '深圳', '杭州'][mt_rand(0, 4)],
                'os_name' => ['Windows', 'macOS', 'Linux'][mt_rand(0, 2)],
                'browser_name' => ['Chrome', 'Firefox', 'Safari'][mt_rand(0, 2)],
                'device_type' => 'desktop',
                'date' => now()->subHours(mt_rand(1, 72)),
                'last_date' => now(),
            ]);

            $session = VisitorSession::create([
                'session_uuid_binary' => Uuid::uuid4()->getBytes(),
                'visitor_id' => $visitor->visitor_id,
                'website_id' => $website->website_id,
                'date' => now()->subHours(mt_rand(1, 72)),
                'total_events' => mt_rand(5, 30),
            ]);

            SessionReplay::create([
                'session_id' => $session->session_id,
                'visitor_id' => $visitor->visitor_id,
                'website_id' => $website->website_id,
                'is_offloaded' => false,
                'datetime' => now()->subHours(mt_rand(1, 72)),
            ]);

            $events = $this->generateRrwebEvents();
            $key = 'session_replay_chunk_'.md5($session->session_id.'_0_'.uniqid('', true));
            Cache::put($key, $events, now()->addDays(30));
            Cache::put("session_replay_keys_{$session->session_id}", [$key], now()->addDays(30));
        }

        $this->command->info('Demo data created!');
    }

    /**
     * @return list<array<string, mixed>>
     */
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
                            [
                                'type' => 1, 'name' => 'head', 'childNodes' => [
                                    ['type' => 1, 'name' => 'title', 'childNodes' => [
                                        ['type' => 3, 'textContent' => 'Example Page'],
                                    ]],
                                    ['type' => 1, 'name' => 'style', 'attributes' => ['type' => 'text/css'], 'childNodes' => [
                                        ['type' => 3, 'textContent' => 'body{font-family:system-ui,sans-serif;margin:0;padding:40px;color:#333}h1{color:#1a1a1a;margin-bottom:16px}p{line-height:1.6;margin-bottom:12px}nav{margin-bottom:24px}nav a{margin-right:16px;color:#0066cc;text-decoration:none}.hero{background:#f8f9fa;padding:32px;border-radius:8px;margin-bottom:24px}.content{display:grid;grid-template-columns:1fr 1fr;gap:24px}'],
                                    ]],
                                ],
                            ],
                            [
                                'type' => 1, 'name' => 'body', 'childNodes' => [
                                    [
                                        'type' => 1, 'name' => 'nav', 'childNodes' => [
                                            ['type' => 1, 'name' => 'a', 'attributes' => ['href' => '/'], 'childNodes' => [['type' => 3, 'textContent' => 'Home']]],
                                            ['type' => 1, 'name' => 'a', 'attributes' => ['href' => '/about'], 'childNodes' => [['type' => 3, 'textContent' => 'About']]],
                                            ['type' => 1, 'name' => 'a', 'attributes' => ['href' => '/contact'], 'childNodes' => [['type' => 3, 'textContent' => 'Contact']]],
                                        ],
                                    ],
                                    [
                                        'type' => 1, 'name' => 'div', 'attributes' => ['class' => 'hero'], 'childNodes' => [
                                            ['type' => 1, 'name' => 'h1', 'childNodes' => [['type' => 3, 'textContent' => 'Welcome to Our Website']]],
                                            ['type' => 1, 'name' => 'p', 'childNodes' => [['type' => 3, 'textContent' => 'We are glad you are here. Explore our services and get in touch.']]],
                                        ],
                                    ],
                                    [
                                        'type' => 1, 'name' => 'div', 'attributes' => ['class' => 'content'], 'childNodes' => [
                                            [
                                                'type' => 1, 'name' => 'div', 'childNodes' => [
                                                    ['type' => 1, 'name' => 'h2', 'childNodes' => [['type' => 3, 'textContent' => 'Our Services']]],
                                                    ['type' => 1, 'name' => 'p', 'childNodes' => [['type' => 3, 'textContent' => 'We offer a range of analytics and monitoring solutions for your business.']]],
                                                ],
                                            ],
                                            [
                                                'type' => 1, 'name' => 'div', 'childNodes' => [
                                                    ['type' => 1, 'name' => 'h2', 'childNodes' => [['type' => 3, 'textContent' => 'Get Started']]],
                                                    ['type' => 1, 'name' => 'p', 'childNodes' => [['type' => 3, 'textContent' => 'Sign up today and start tracking your website performance.']]],
                                                    ['type' => 1, 'name' => 'a', 'attributes' => ['href' => '/signup', 'class' => 'btn'], 'childNodes' => [['type' => 3, 'textContent' => 'Sign Up Now']]],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'type' => 1, 'name' => 'footer', 'childNodes' => [
                                            ['type' => 1, 'name' => 'p', 'childNodes' => [['type' => 3, 'textContent' => '© 2026 Example Company. All rights reserved.']]],
                                        ],
                                    ],
                                ],
                            ],
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

    /**
     * Generate rrweb snapshot events for heatmap DOM snapshot (new format)
     * These events can be rendered by rrweb-player to show the actual webpage
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generateSnapshotEvents(string $pageName): array
    {
        $ts = now()->getPreciseTimestamp(3);

        return [
            [
                'type' => 4,
                'data' => ['href' => 'https://example.com/'.($pageName === 'Homepage' ? '' : $pageName), 'width' => 1920, 'height' => 1080],
                'timestamp' => $ts,
            ],
            [
                'type' => 2,
                'data' => [
                    'node' => [
                        'type' => 0,
                        'childNodes' => [[
                            'type' => 1, 'name' => 'html', 'childNodes' => [
                                [
                                    'type' => 1, 'name' => 'head', 'childNodes' => [
                                        ['type' => 1, 'name' => 'title', 'childNodes' => [
                                            ['type' => 3, 'textContent' => $pageName.' - Example'],
                                        ]],
                                        ['type' => 1, 'name' => 'style', 'attributes' => ['type' => 'text/css'], 'childNodes' => [
                                            ['type' => 3, 'textContent' => 'body{font-family:system-ui,sans-serif;margin:0;padding:40px;color:#333}h1{color:#1a1a1a;margin-bottom:16px}p{line-height:1.6;margin-bottom:12px}nav{margin-bottom:24px}nav a{margin-right:16px;color:#0066cc}'],
                                        ]],
                                    ],
                                ],
                                [
                                    'type' => 1, 'name' => 'body', 'childNodes' => [
                                        [
                                            'type' => 1, 'name' => 'h1', 'childNodes' => [
                                                ['type' => 3, 'textContent' => $pageName],
                                            ],
                                        ],
                                        [
                                            'type' => 1, 'name' => 'p', 'childNodes' => [
                                                ['type' => 3, 'textContent' => 'This is the '.strtolower($pageName).' page content.'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ]],
                    ],
                ],
                'timestamp' => $ts + 1,
            ],
        ];
    }
}
