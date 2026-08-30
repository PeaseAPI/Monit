<?php

/**
 * Offload 停用入口：关闭功能标记（Cron offload 任务据此停止）
 */

\App\Support\Settings::set('offload.is_enabled', false);
