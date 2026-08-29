<?php

namespace App\Http\Controllers;

use App\Services\LicenseManager;
use Illuminate\Http\Request;

/**
 * 管理后台 - 授权许可（规格书 §15.2：Ed25519 离线签名 License）
 */
class AdminLicense extends Controller
{
    public function index(Request $request)
    {
        $status = LicenseManager::status($request->boolean('refresh'));

        return view('admin.license.index', [
            'status' => $status,
            'license' => $status['data'],
            'licensePath' => LicenseManager::licensePath(),
            'currentHost' => strtolower((string) (parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost')),
        ])->with('adminNav', 'license');
    }

    /**
     * 上传 license.json 替换并立即重验
     */
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'license_file' => ['required', 'file', 'max:64'],
        ]);

        $content = file_get_contents($validated['license_file']->getRealPath());
        json_decode((string) $content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['license_file' => __('admin.license_invalid_json')]);
        }

        file_put_contents(LicenseManager::licensePath(), $content);

        $status = LicenseManager::status(true);

        return back()->with(
            $status['valid'] ? 'success' : 'error',
            __('admin.license_uploaded_' . ($status['valid'] ? 'valid' : 'invalid'), ['reason' => $status['reason']]),
        );
    }

    /**
     * 强制重新验证（清除缓存）
     */
    public function refresh()
    {
        $status = LicenseManager::status(true);

        return back()->with('info', __('admin.license_rechecked', ['reason' => $status['reason']]));
    }
}
