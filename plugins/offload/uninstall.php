<?php

/**
 * Offload 卸载钩子：无需删表（复用 sessions_replays.is_offloaded 标记），
 * 仅关闭功能标记并将已 offload 标记复位（回放回退为本地模式）。
 */

use App\Support\Settings;
use Illuminate\Support\Facades\DB;

Settings::set('offload.is_enabled', false);

DB::table('sessions_replays')->where('is_offloaded', true)->update(['is_offloaded' => false]);
