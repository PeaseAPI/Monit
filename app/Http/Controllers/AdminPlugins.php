<?php

namespace App\Http\Controllers;

use App\Support\PluginManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 管理后台 - 插件管理（规格书 §14：状态机 uninstalled → installed → active）
 */
class AdminPlugins extends Controller
{
    public function index(Request $request)
    {
        $plugins = PluginManager::scan();

        return view('admin.plugins.index', [
            'plugins' => $plugins,
            'totalActive' => count(array_filter($plugins, fn ($p) => $p['active'])),
        ])->with('adminNav', 'plugins');
    }

    public function install(Request $request, string $plugin): RedirectResponse
    {
        return $this->guard($plugin, fn () => PluginManager::install($plugin), 'installed');
    }

    public function activate(Request $request, string $plugin): RedirectResponse
    {
        return $this->guard($plugin, fn () => PluginManager::activate($plugin), 'activated');
    }

    public function deactivate(Request $request, string $plugin): RedirectResponse
    {
        return $this->guard($plugin, fn () => PluginManager::deactivate($plugin), 'deactivated');
    }

    public function uninstall(Request $request, string $plugin): RedirectResponse
    {
        return $this->guard($plugin, fn () => PluginManager::uninstall($plugin), 'uninstalled');
    }

    /**
     * 保存插件设置（settings.json 定义的键）
     */
    public function updateSettings(Request $request, string $plugin): RedirectResponse
    {
        $meta = PluginManager::meta($plugin);

        if ($meta === null) {
            return back()->with('error', __('admin.plugins_not_found'));
        }

        $values = [];

        foreach ($meta['settings'] as $key => $definition) {
            $type = $definition['type'] ?? 'text';

            if ($type === 'bool') {
                $values[$key] = $request->boolean($key);
            } else {
                $values[$key] = $request->input($key, $definition['default'] ?? '');
            }
        }

        PluginManager::saveSettings($plugin, $values);

        return back()->with('success', __('admin.plugin_settings_saved'));
    }

    protected function guard(string $plugin, callable $action, string $state): RedirectResponse
    {
        if (! PluginManager::exists($plugin)) {
            return back()->with('error', __('admin.plugins_not_found'));
        }

        $action();

        return back()->with('success', __('admin.plugins_state_'.$state));
    }
}
