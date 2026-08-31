<?php

/*
|--------------------------------------------------------------------------
| Monit 安装向导路由（规格书 §15.3 安装器 / §19 部署）
|--------------------------------------------------------------------------
| 该文件在 bootstrap/app.php 中以【无中间件】方式注册（不走 web 组）：
| 未安装时 Session（database 驱动无表）/ Cookie 加密（APP_KEY 未生成）均不可用，
| 走 web 组必然 500；向导页面自包含样式、无需 session/CSRF。
| 关联：App\Http\Middleware\EnsureInstalled（未安装时其余请求 302 到这里）
*/

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'index'])->name('install');
Route::post('/install/database', [InstallController::class, 'database'])->name('install.database');
Route::get('/install/admin', [InstallController::class, 'showAdmin'])->name('install.admin');
Route::post('/install/admin', [InstallController::class, 'admin'])->name('install.admin.submit');
