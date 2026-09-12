<?php

/**
 * Affiliate 卸载钩子（空操作）。
 *
 * 配置源统一：运行时消费的 affiliate.affiliate_* 设置由管理后台
 * 「系统设置 → Affiliate」组管理，卸载插件不再隐式改写（原写入的
 * affiliate.is_enabled / commission_percentage 为零消费死键）。
 * 插件自身数据（plugins 表行）由插件管理器卸载流程清理。
 */
