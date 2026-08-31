<?php

/*
|--------------------------------------------------------------------------
| Monit 安装向导路由（规格书 §15.3 安装器 / §19 部署）
|--------------------------------------------------------------------------
| 五步向导（MySQL 唯一支持）：环境检测 → 目录权限 → 数据库配置 → 站点与管理员 → 完成。
| 该文件在 bootstrap/app.php 中以【无中间件】方式注册（不走 web 组）：
| 未安装时 Session（database 驱动无表）/ Cookie 加密（APP_KEY 未生成）均不可用，
| 走 web 组必然 500；向导页面自包含样式、无需 session/CSRF。
| 步骤推进的前置校验在控制器内完成（环境/权限实时计算、users 表存在性、installed.lock）。
| 关联：App\Http\Middleware\EnsureInstalled（未安装时其余请求 302 到这里）
*/

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/install', [InstallController::class, 'requirements'])->name('install');
Route::post('/install/requirements', [InstallController::class, 'checkRequirements'])->name('install.requirements.submit');
Route::get('/install/permissions', [InstallController::class, 'permissions'])->name('install.permissions');
Route::post('/install/permissions', [InstallController::class, 'checkPermissions'])->name('install.permissions.submit');
Route::get('/install/database', [InstallController::class, 'showDatabase'])->name('install.database');
Route::post('/install/test-db', [InstallController::class, 'testConnection'])->name('install.test-db');
Route::post('/install/database', [InstallController::class, 'database'])->name('install.database.submit');
Route::get('/install/admin', [InstallController::class, 'showAdmin'])->name('install.admin');
Route::post('/install/admin', [InstallController::class, 'admin'])->name('install.admin.submit');
Route::get('/install/finish', [InstallController::class, 'finish'])->name('install.finish');
