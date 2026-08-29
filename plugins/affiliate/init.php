<?php

/**
 * Affiliate 启动入口：开启前台推荐入口（settings.affiliate.is_enabled = true）
 */

\App\Support\Settings::set('affiliate.is_enabled', true);
\App\Support\Settings::set(
    'affiliate.commission_percentage',
    (int) \App\Support\PluginManager::setting('affiliate', 'commission_percentage', 20),
);
