<?php

use App\Support\PluginManager;
use App\Support\Settings;

/**
 * Affiliate 启动入口：开启前台推荐入口（settings.affiliate.is_enabled = true）
 */
Settings::set('affiliate.is_enabled', true);
Settings::set(
    'affiliate.commission_percentage',
    (int) PluginManager::setting('affiliate', 'commission_percentage', 20),
);
