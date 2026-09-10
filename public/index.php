<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 第十八轮：移除 X-Powered-By（expose_php=On 时 PHP SAPI 层附加 "PHP/x.y.z"，
// 向指纹扫描暴露运行时版本）。必须在此 SAPI 层移除——Symfony Response 的
// headers->remove() 看不到 SAPI 附加头。生产仍建议 php.ini expose_php=Off。
header_remove('X-Powered-By');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
