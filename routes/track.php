<?php

/*
|--------------------------------------------------------------------------
| Monit 高频采集路由（M23 性能优化 · 规格书 §4.1）
|--------------------------------------------------------------------------
| 该文件在 bootstrap/app.php 中以【无中间件】方式注册（不走 web 组）：
| 高频跟踪请求无需 Session / Cookie / CSRF / 维护检查等开销，
| 相比走 web 组每次请求节省 session 启动、cookie 加密等成本。
| 关联：app/Http/Controllers/PixelTrackController（响应恒 204 + CORS）
*/

use App\Http\Controllers\PixelTrackController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/pixel-track/{pixel_key}', PixelTrackController::class)
    ->name('pixel.track');

Route::options('/pixel-track/{pixel_key}', [PixelTrackController::class, 'preflight'])
    ->name('pixel.track.preflight');
