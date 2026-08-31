<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SessionEvent;
use App\Models\User;
use App\Models\VisitorSession;
use App\Models\Website;
use App\Models\WebsiteVisitor;
use Illuminate\Http\Request;

/**
 * 管理后台 - 统计概览
 * 规格书 §6.3.5 / 附B：AdminStatistics(index, database, local_files, growth, users, payments)
 */
class AdminStatistics extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $activeUsers = User::where('status', 1)->count();
        $newUsersToday = User::whereDate('created_at', today())->count();
        $totalWebsites = Website::count();
        $enabledWebsites = Website::where('is_enabled', true)->count();
        $totalPayments = Payment::where('status', 1)->count();
        $totalRevenue = Payment::where('status', 1)->sum('total_amount') ?? 0;
        $monthlyRevenue = Payment::where('status', 1)->whereMonth('datetime', now()->month)->whereYear('datetime', now()->year)->sum('total_amount') ?? 0;
        $totalVisitors = WebsiteVisitor::count();
        $totalSessions = VisitorSession::count();
        $totalEvents = SessionEvent::count();
        $planDistribution = User::groupBy('plan_id')->selectRaw('plan_id, count(*) as count')->get();
        $dailyActiveUsers = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = User::whereDate('last_activity', '>=', $date)->whereDate('last_activity', '<', now()->subDays($i - 1)->format('Y-m-d'))->count();
            $dailyActiveUsers[] = ['date' => $date, 'count' => $count];
        }

        return view('admin.statistics.index', compact('totalUsers', 'activeUsers', 'newUsersToday', 'totalWebsites', 'enabledWebsites', 'totalPayments', 'totalRevenue', 'monthlyRevenue', 'totalVisitors', 'totalSessions', 'totalEvents', 'planDistribution', 'dailyActiveUsers'))->with('adminNav', 'statistics');
    }

    public function database()
    {
        $tables = ['users', 'websites', 'plans', 'payments', 'domains', 'codes', 'taxes'];
        $stats = [];
        foreach ($tables as $table) {
            try {
                $stats[$table] = \DB::table($table)->count();
            } catch (\Throwable) {
                $stats[$table] = -1;
            }
        }

        return view('admin.statistics.database', compact('stats'))->with('adminNav', 'statistics');
    }

    public function localFiles()
    {
        $uploadPath = storage_path('app/public');
        $fileStats = is_dir($uploadPath) ? $this->getDirectoryStats($uploadPath) : ['total_files' => 0, 'total_size' => 0, 'directories' => []];

        return view('admin.statistics.local-files', compact('fileStats'))->with('adminNav', 'statistics');
    }

    public function growth()
    {
        $userGrowth = $this->getGrowthData(User::class, 30);
        $websiteGrowth = $this->getGrowthData(Website::class, 30);
        $paymentGrowth = $this->getGrowthData(Payment::class, 30);

        return view('admin.statistics.growth', compact('userGrowth', 'websiteGrowth', 'paymentGrowth'))->with('adminNav', 'statistics');
    }

    public function users(Request $request)
    {
        $days = match ($request->query('period', '30d')) {
            '7d' => 7, '90d' => 90, default => 30
        };
        $newUsers = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $newUsers[] = ['date' => $date, 'count' => User::whereDate('created_at', $date)->count()];
        }
        $bySource = User::selectRaw('source, count(*) as count')->whereDate('created_at', '>=', now()->subDays($days))->groupBy('source')->get();
        $byCountry = User::selectRaw('country, count(*) as count')->whereDate('created_at', '>=', now()->subDays($days))->groupBy('country')->orderByDesc('count')->limit(20)->get();

        return view('admin.statistics.users', compact('newUsers', 'bySource', 'byCountry', 'days'))->with('adminNav', 'statistics');
    }

    public function payments(Request $request)
    {
        $days = match ($request->query('period', '30d')) {
            '7d' => 7, '90d' => 90, default => 30
        };
        $revenue = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $revenue[] = ['date' => $date, 'amount' => Payment::where('status', 1)->whereDate('datetime', $date)->sum('total_amount') ?? 0];
        }
        $byProcessor = Payment::selectRaw('payment_processor as processor, count(*) as count, sum(total_amount) as total')->where('status', 1)->whereDate('datetime', '>=', now()->subDays($days))->groupBy('payment_processor')->get();
        $byPlan = Payment::selectRaw('plan_id, count(*) as count, sum(total_amount) as total')->where('status', 1)->whereDate('datetime', '>=', now()->subDays($days))->groupBy('plan_id')->get();

        return view('admin.statistics.payments', compact('revenue', 'byProcessor', 'byPlan', 'days'))->with('adminNav', 'statistics');
    }

    private function getDirectoryStats(string $path): array
    {
        $totalSize = 0;
        $totalFiles = 0;
        $directories = [];
        foreach (scandir($path) as $entry) {
            if (in_array($entry, ['.', '..'])) {
                continue;
            }
            $fullPath = $path.'/'.$entry;
            if (is_dir($fullPath)) {
                $totalSize += $this->dirSize($fullPath);
                $totalFiles += $this->dirFileCount($fullPath);
            } else {
                $totalSize += filesize($fullPath);
                $totalFiles++;
            }
        }

        return ['total_files' => $totalFiles, 'total_size' => $totalSize, 'directories' => $directories];
    }

    private function dirSize(string $path): int
    {
        $s = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $f) {
            $s += $f->getSize();
        }

return $s;
    }

    private function dirFileCount(string $path): int
    {
        $c = 0;
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path)) as $f) {
            if ($f->isFile()) {
                $c++;
            }
        }

return $c;
    }

    private function getGrowthData(string $model, int $days): array
    {
        $d = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $d[] = ['date' => $date, 'count' => $model::whereDate('created_at', '<=', $date)->count()];
        }

return $d;
    }
}
