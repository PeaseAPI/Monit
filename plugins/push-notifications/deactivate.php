<?php

/**
 * Push Notifications 停用钩子（空操作）。
 *
 * 配置源统一：Cron 发送任务的停止依据是插件激活态
 * （PluginManager::isActive）与 campaign.is_enabled，不读
 * settings 表；原停用写入的 push_notifications.is_enabled
 * 为零消费死键，不再维护。
 */
