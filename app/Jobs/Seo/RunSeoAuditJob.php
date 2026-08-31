<?php

namespace App\Jobs\Seo;

use App\Models\User;
use App\Services\Seo\AuditEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 队列化 SEO 审计（批量 / sitemap / 定时复审共用）
 */
class RunSeoAuditJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    public function __construct(
        public readonly string $url,
        public readonly ?int $userId = null,
        public readonly string $type = 'single',
        public readonly array $options = [],
    ) {}

    public function handle(AuditEngine $engine): void
    {
        $user = $this->userId !== null ? User::find($this->userId) : null;

        $engine->run($this->url, $user, $this->type, $this->options);
    }
}
