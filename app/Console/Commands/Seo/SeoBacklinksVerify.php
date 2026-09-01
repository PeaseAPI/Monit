<?php

namespace App\Console\Commands\Seo;

use App\Models\SeoBacklink;
use App\Services\Seo\BacklinkChecker;
use App\Support\Settings;
use Illuminate\Console\Command;
use Throwable;

/**
 * 反链活性定期重验：抓源页匹配目标站链接，标记 active / lost
 * 网络性失败保持原状态，等待下轮
 */
class SeoBacklinksVerify extends Command
{
    protected $signature = 'monit:seo-backlinks-verify';

    protected $description = '重验反链活性并更新 active/lost 状态';

    public function handle(BacklinkChecker $checker): int
    {
        if (! filter_var(Settings::get('seo.audits_is_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $this->info('SEO 模块已停用（seo.audits_is_enabled），跳过。');

            return self::SUCCESS;
        }

        $stats = ['active' => 0, 'lost' => 0, 'kept' => 0];

        // pending 优先（新录入待首验），active/lost 按 last_checked_at 最旧优先
        SeoBacklink::query()
            ->whereIn('status', ['pending', 'active', 'lost'])
            ->orderByRaw('ISNULL(last_checked_at) DESC, last_checked_at ASC')
            ->where(function ($q) {
                $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<=', now()->subDay());
            })
            ->chunkById(50, function ($links) use ($checker, &$stats) {
                foreach ($links as $link) {
                    try {
                        $status = $checker->verify($link);
                        $stats[$status === 'active' ? 'active' : ($status === 'lost' ? 'lost' : 'kept')]++;
                    } catch (Throwable $e) {
                        report($e);
                        $stats['kept']++;
                    }
                }
            });

        $this->info(sprintf(
            '反链重验：active %d、lost %d、保持 %d。',
            $stats['active'], $stats['lost'], $stats['kept'],
        ));

        return self::SUCCESS;
    }
}
