<?php

/**
 * Affiliate 卸载钩子：关闭前台推荐入口并清理设置
 */

\App\Support\Settings::set('affiliate.is_enabled', false);
\App\Support\Settings::set('affiliate.commission_percentage', 0);
