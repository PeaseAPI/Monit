<?php

namespace App\Console\Commands;

use App\Models\GoalConversion;
use App\Models\SessionEvent;
use App\Models\Website;
use App\Models\WebsiteGoal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 转化追踪 Cron
 * 规格书 §7：Cron 任务 - 每小时扫描页面浏览事件并匹配目标
 */
class TrackConversionsCommand extends Command
{
    protected $signature = 'monit:track-conversions';
    protected $description;

    public function __construct()
    {
        parent::__construct();
        $this->description = __('console.conversions_desc');
    }

    public function handle(): int
    {
        $now = now();

        // 查找最近1小时内的新页面浏览事件
        $events = SessionEvent::where('type', 'pageview')
            ->where('datetime', '>=', $now->copy()->subHour())
            ->where('datetime', '<', $now)
            ->get();

        $conversions = 0;

        foreach ($events as $event) {
            $website = $event->website;
            if (!$website || !$website->is_enabled) {
                continue;
            }

            $url = $event->url ?? '';
            $path = parse_url($url, PHP_URL_PATH) ?? '/';

            // 查找匹配的目标
            $goals = WebsiteGoal::where('website_id', $website->website_id)
                ->where('is_enabled', true)
                ->get();

            foreach ($goals as $goal) {
                $matched = false;

                if ($goal->type === 'pageview' && $goal->path) {
                    // 精确路径匹配
                    if ($path === $goal->path) {
                        $matched = true;
                    }
                    // 前缀匹配 (路径以目标路径开头)
                    elseif (str_starts_with($path, $goal->path . '/')) {
                        $matched = true;
                    }
                }

                if ($matched) {
                    $existing = GoalConversion::where('goal_id', $goal->goal_id)
                        ->where('session_id', $event->session_id)
                        ->exists();

                    if (!$existing) {
                        GoalConversion::create([
                            'goal_id' => $goal->goal_id,
                            'website_id' => $website->website_id,
                            'session_id' => $event->session_id,
                            'visitor_id' => $event->visitor_id,
                            'url' => $url,
                        ]);

                        $conversions++;
                    }
                }
            }
        }

                $this->info(__('console.conversions_processed', ['events' => $events->count(), 'conversions' => $conversions]));

        return self::SUCCESS;
    }
}