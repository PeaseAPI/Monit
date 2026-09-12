<?php

/**
 * Affiliate 启动入口：注册插件端点。
 *
 * 配置源统一：前台推荐入口开关与佣金参数由管理后台
 * 「系统设置 → Affiliate」组（settings 表 affiliate.*）管理，
 * 插件激活态由 plugins.is_active 表达——本钩子不再向 settings
 * 表写入开关（原 affiliate.is_enabled / commission_percentage
 * 为零消费死键，且每次请求触发一次 DB 写 + 缓存失效）。
 */
