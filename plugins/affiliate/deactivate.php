<?php

/**
 * Affiliate 停用钩子（空操作）。
 *
 * 配置源统一：前台推荐入口开关由管理后台「系统设置 → Affiliate」
 * 组（settings 表 affiliate.affiliate_is_enabled）管理，插件停用
 * 不再隐式改写设置（原写入的 affiliate.is_enabled 为零消费死键）。
 * 停用插件即关闭全部插件端点（路由激活态由 plugins.is_active 表达）。
 */

