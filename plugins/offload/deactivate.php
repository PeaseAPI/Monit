<?php

use App\Support\Settings;

/**
 * Offload 停用入口：关闭功能标记（Cron offload 任务据此停止）
 */
Settings::set('offload.is_enabled', false);
