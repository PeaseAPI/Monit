<?php

/**
 * Email Shield 卸载钩子：清理缓存数据（本插件无自建表）
 */

use Illuminate\Support\Facades\Cache;

Cache::forget('plugin.email-shield');
