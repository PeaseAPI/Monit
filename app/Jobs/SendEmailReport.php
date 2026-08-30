<?php

namespace App\Jobs;

use App\Mail\EmailReport;
use App\Models\User;
use App\Models\Website;
use App\Services\StatisticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * 发送邮件报表任务（规格书 §13.1：email_reports）
 */
class SendEmailReport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Website $website,
        public User $user,
    ) {}

    public function handle(): void
    {
        $stats = StatisticsService::for($this->website)
            ->lastDays(7)
            ->overview();

        Mail::to($this->user)->send(new EmailReport($this->website, $stats));

        $this->website->update(['email_reports_last_date' => now()]);
    }
}
