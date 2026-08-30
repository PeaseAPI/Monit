<?php

/**
 * Push Notifications 停用入口：关闭功能标记（Cron 发送任务据此停止）
 */

\App\Support\Settings::set('push_notifications.is_enabled', false);
