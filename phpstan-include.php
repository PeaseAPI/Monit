<?php

// PHPStan includes 侧加载：config 加载期即执行（早于扩展容器编译），覆盖并行 worker 冷启动。
// Laravel 13 不再定义全局常量 LARAVEL_VERSION，而 larastan（StubFilesExtension 等）依赖它。
// 注意：includes 的 .php 文件须返回数组（NEON 结构），副作用在本文件执行。
if (! defined('LARAVEL_VERSION')) {
    define('LARAVEL_VERSION', \Illuminate\Foundation\Application::VERSION);
}

return [];
