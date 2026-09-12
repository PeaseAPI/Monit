<?php

/**
 * Offload 卸载钩子：无需删表（复用 sessions_replays.is_offloaded 标记），
 * 仅将已 offload 标记复位（回放回退为本地模式）。
 *
 * 配置源统一：原卸载写入的 offload.is_enabled 为零消费死键，不再维护。
 */

use Illuminate\Support\Facades\DB;

DB::table('sessions_replays')->where('is_offloaded', true)->update(['is_offloaded' => false]);
