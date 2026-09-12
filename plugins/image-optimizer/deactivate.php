<?php

/**
 * Image Optimizer 停用钩子（空操作）。
 *
 * 配置源统一：插件停用即关闭全部插件端点与上传拦截
 * （激活态由 plugins.is_active 表达）；原停用写入的
 * image_optimizer.is_enabled 为零消费死键，不再维护。
 */

