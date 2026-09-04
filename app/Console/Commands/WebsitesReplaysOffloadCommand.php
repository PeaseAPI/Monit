<?php

namespace App\Console\Commands;

use App\Models\EventChild;
use App\Models\SessionReplay;
use App\Support\ObjectStorage;
use App\Support\PluginManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 会话回放 Offload Cron（规格书 §13.1 websites_replays_offload）
 * 每小时运行；插件 offload 启用时：24h 前未 offload 的回放序列化上传对象存储（LIMIT 25/批）。
 * M17 §14.8：驱动扩展为 S3/MinIO/阿里云 OSS/腾讯云 COS（ObjectStorage 工厂统一分发）。
 */
class WebsitesReplaysOffloadCommand extends Command
{
    protected $signature = 'monit:websites-replays-offload';

    protected $description = '将 24h 前的会话回放序列化上传对象存储（Offload 插件：S3/OSS/COS）';

    public function handle(): int
    {
        if (! PluginManager::isActive('offload')) {
            $this->info('offload 插件未启用，跳过');

            return self::SUCCESS;
        }

        $driver = ObjectStorage::driver();

        if (! ObjectStorage::isConfigured()) {
            $this->error("存储驱动 [{$driver}] 凭据未配置（后台「存储卸载」设置或插件设置）");

            return self::FAILURE;
        }

        $storage = ObjectStorage::make();

        $batchSize = max(1, (int) PluginManager::setting('offload', 'batch_size', 25));
        $deleteAfterUpload = (bool) PluginManager::setting('offload', 'delete_after_upload', true);

        // 24h 前未 offload 的回放
        $replays = SessionReplay::where('is_offloaded', false)
            ->where('datetime', '<', now()->subDay())
            ->orderBy('replay_id')
            ->limit($batchSize)
            ->get();

        if ($replays->isEmpty()) {
            $this->info('无待 offload 回放');

            return self::SUCCESS;
        }

        $uploaded = 0;
        $skipped = 0;

        foreach ($replays as $replay) {
            $session = $replay->session;

            // 优先从缓存取回放事件（handleReplayChunk 存入缓存）
            $events = [];
            if ($session) {
                $cacheKey = "session_replay_keys_{$session->session_id}";
                $keys = Cache::get($cacheKey, []);

                foreach ($keys as $chunkKey) {
                    $chunk = Cache::get($chunkKey);
                    if (is_array($chunk)) {
                        $events = array_merge($events, $chunk);
                    }
                }
            }

            // 回退：如果缓存无数据，从 EventChild 取（兼容旧数据）
            if (empty($events)) {
                $events = EventChild::where('session_id', $replay->session_id)
                    ->orderBy('event_child_id')
                    ->get(['type', 'data', 'count', 'date'])
                    ->map(fn ($e) => [
                        'type' => $e->type,
                        'data' => $e->data,
                        'count' => $e->count,
                        'date' => (string) $e->date,
                    ])
                    ->values()
                    ->all();
            }

            $key = 'replays/'.$replay->website_id.'/'.$replay->session_id.'.json';
            $payload = json_encode([
                'replay_id' => $replay->replay_id,
                'session_id' => $replay->session_id,
                'visitor_id' => $replay->visitor_id,
                'website_id' => $replay->website_id,
                'datetime' => (string) $replay->datetime,
                'events' => $events,
            ], JSON_UNESCAPED_UNICODE);

            [$status, , $error] = $storage->put($key, (string) $payload, 'application/json');

            if ($status >= 200 && $status < 300) {
                $replay->update(['is_offloaded' => true]);

                // offload 后清理缓存 chunk
                if ($session && $deleteAfterUpload) {
                    $cacheKey = "session_replay_keys_{$session->session_id}";
                    $keys = Cache::get($cacheKey, []);
                    foreach ($keys as $chunkKey) {
                        Cache::forget($chunkKey);
                    }
                    Cache::forget($cacheKey);

                    // 同时清理 EventChild 中的旧回放数据
                    EventChild::where('session_id', $replay->session_id)->delete();
                }

                $uploaded++;
                $this->line("  [OK] {$key} (".count($events).' events)');
            } else {
                $skipped++;
                $this->warn("  [FAIL {$status}] {$key}".($error !== '' ? " ({$error})" : ''));
            }
        }

        $this->info("已上传 {$uploaded} 条回放，失败 {$skipped} 条");

        return self::SUCCESS;
    }
}
