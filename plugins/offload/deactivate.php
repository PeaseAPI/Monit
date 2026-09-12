<?php

/**
 * Offload 停用钩子（空操作）。
 *
 * 配置源统一：Cron offload 任务停止依据是插件激活态
 * （PluginManager::isActive），原停用写入的 offload.is_enabled
 * 为零消费死键，不再维护。
 */
