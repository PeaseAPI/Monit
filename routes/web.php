<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PixelTrackController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Monit 路由表（依据规格书 §2）
|--------------------------------------------------------------------------
*/

// 像素采集端点（公开，CORS 全开）
Route::match(['get', 'post'], '/pixel-track/{pixel_key}', PixelTrackController::class)
    ->name('pixel.track');
Route::options('/pixel-track/{pixel_key}', [PixelTrackController::class, 'preflight']);

// 访客态路由
Route::middleware('guest')->group(function (): void {
    Route::get('/', fn () => redirect()->route('dashboard'))->name('index');

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

// 登录用户路由
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/install/{website}', [DashboardController::class, 'install'])
        ->middleware('can:own,website')->name('dashboard.install');

    // 网站管理
    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::get('/website-create', [WebsiteController::class, 'create'])->name('websites.create');
    Route::post('/website-create', [WebsiteController::class, 'store'])->name('websites.store');
    Route::get('/website-update/{website}', [WebsiteController::class, 'edit'])
        ->middleware('can:own,website')->name('websites.edit');
    Route::put('/website-update/{website}', [WebsiteController::class, 'update'])
        ->middleware('can:own,website')->name('websites.update');
    Route::delete('/website-delete/{website}', [WebsiteController::class, 'destroy'])
        ->middleware('can:own,website')->name('websites.destroy');
});
