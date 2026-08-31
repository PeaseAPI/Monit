<?php

namespace App\Http\Controllers;

use App\Models\Website;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 用户中心 - 网站批量导入
 * 规格书 §6.2.3：WebsitesImport
 */
class WebsitesImportController extends Controller
{
    public function index()
    {
        return view('websites.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'urls' => ['nullable', 'string', 'max:10000'],
            'csv_file' => ['nullable', 'file', 'mimes:csv,txt', 'max:1024'],
            'tracking_type' => ['required', 'in:advanced,lightweight'],
            'timezone' => ['required', 'timezone'],
        ]);

        $urls = [];

        // 从文本框读取
        if (! empty($validated['urls'])) {
            $urls = array_filter(array_map('trim', explode("\n", $validated['urls'])));
        }

        // 从 CSV 文件读取（列：name,url）
        if ($request->hasFile('csv_file')) {
            $csv = $request->file('csv_file');
            $handle = fopen($csv->getRealPath(), 'r');
            // 跳过表头
            fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== false) {
                if (isset($row[1]) && filter_var($row[1], FILTER_VALIDATE_URL)) {
                    $urls[] = trim($row[1]);
                } elseif (isset($row[0]) && filter_var($row[0], FILTER_VALIDATE_URL)) {
                    $urls[] = trim($row[0]);
                }
            }
            fclose($handle);
        }

        if (empty($urls)) {
            return back()->withErrors(['urls' => __('validation.import_urls_required')]);
        }

        $user = $request->user();
        $planSettings = $user->getPlanSettings();
        $limit = $planSettings['websites_limit'] ?? -1;
        $current = $user->websites()->count();

        $created = 0;
        $skipped = 0;

        foreach ($urls as $url) {
            if ($limit !== -1 && $current + $created >= $limit) {
                break;
            }

            $parts = parse_url(trim($url));
            if (! isset($parts['host'])) {
                $skipped++;

                continue;
            }

            $host = strtolower(preg_replace('/^www\./', '', $parts['host']));

            // 检查是否已存在该域名的网站（同一用户下）
            if ($user->websites()->where('host', $host)->exists()) {
                $skipped++;

                continue;
            }

            $pixelKey = $this->generatePixelKey();

            Website::create([
                'user_id' => $user->user_id,
                'pixel_key' => $pixelKey,
                'name' => $host,
                'scheme' => $parts['scheme'] ?? 'https',
                'host' => $host,
                'path' => $parts['path'] ?? '',
                'tracking_type' => $validated['tracking_type'],
                'timezone' => $validated['timezone'],
                'bot_exclusion_is_enabled' => true,
            ]);

            $created++;
        }

        $message = __('msg.websites_imported', ['count' => $created]);
        if ($skipped > 0) {
            $message .= ' '.__('msg.websites_imported_skipped', ['count' => $skipped]);
        }

        return redirect()->route('websites.index')
            ->with('success', $message);
    }

    protected function generatePixelKey(): string
    {
        do {
            $key = Str::lower(Str::random(32));
        } while (Website::where('pixel_key', $key)->exists());

        return $key;
    }
}
