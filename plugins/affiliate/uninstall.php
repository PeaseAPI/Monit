<?php

use App\Support\Settings;

/**
 * Affiliate 卸载钩子：关闭前台推荐入口并清理设置
 */
Settings::set('affiliate.is_enabled', false);
Settings::set('affiliate.commission_percentage', 0);
