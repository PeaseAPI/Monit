<?php

/**
 * Push Notifications 卸载钩子：删除插件数据表（规格书 §14.2 / §14.5）
 */

use Illuminate\Support\Facades\Schema;

Schema::dropIfExists('push_notifications_subscribers');
Schema::dropIfExists('push_notifications_campaigns');
