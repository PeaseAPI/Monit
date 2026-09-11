<?php

use Illuminate\Foundation\Application;

// Laravel 13 不再定义全局常量 LARAVEL_VERSION，而 larastan（StubFilesExtension 等）依赖它。
// 通过 phpstan bootstrapFiles 在每个分析进程启动时补定义。
if (! defined('LARAVEL_VERSION')) {
    define('LARAVEL_VERSION', Application::VERSION);
}
