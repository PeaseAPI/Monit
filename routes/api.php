<?php

use App\Http\Controllers\Api\v1\{
    AccountController,
    AnalyticsController,
    DomainController,
    ResourcesController,
    WebsiteController as ApiWebsiteController,
    WebsiteDataController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API 路由（规格书 §2）
|--------------------------------------------------------------------------
|
| API 基础前缀: /api/v1
| 认证方式: Bearer Token (用户 api_key)
|
*/

Route::prefix('v1')->middleware('api.key')->group(function (): void {

    // 账户资源（规格书 §8：/api/user、/api/logs、/api/payments、/api/dashboard-views、/api/teams）
    Route::get('/user', [AccountController::class, 'user']);
    Route::get('/logs', [AccountController::class, 'logs']);
    Route::get('/payments', [AccountController::class, 'payments']);
    Route::get('/dashboard-views', [AccountController::class, 'dashboardViewsIndex']);
    Route::post('/dashboard-views', [AccountController::class, 'dashboardViewsStore']);
    Route::put('/dashboard-views/{view}', [AccountController::class, 'dashboardViewsUpdate']);
    Route::delete('/dashboard-views/{view}', [AccountController::class, 'dashboardViewsDestroy']);
    Route::get('/teams', [AccountController::class, 'teamsIndex']);
    Route::post('/teams', [AccountController::class, 'teamsStore']);
    Route::get('/teams/{team}', [AccountController::class, 'teamsShow']);
    Route::delete('/teams/{team}', [AccountController::class, 'teamsDestroy']);
    Route::get('/teams/{team}/members', [AccountController::class, 'teamMembersIndex']);
    Route::delete('/teams/{team}/members/{member}', [AccountController::class, 'teamMembersDestroy']);

    // 域名管理（规格书 §8：/api/domains）
    Route::get('/domains', [DomainController::class, 'index']);
    Route::post('/domains', [DomainController::class, 'store']);
    Route::put('/domains/{domainId}', [DomainController::class, 'update']);
    Route::delete('/domains/{domainId}', [DomainController::class, 'destroy']);

    // 标注（规格书 §8：/api/annotations）
    Route::get('/annotations', [ResourcesController::class, 'annotationsIndex']);
    Route::post('/annotations', [ResourcesController::class, 'annotationsStore']);
    Route::put('/annotations/{annotation}', [ResourcesController::class, 'annotationsUpdate']);
    Route::delete('/annotations/{annotation}', [ResourcesController::class, 'annotationsDestroy']);

    // 网站管理
    Route::get('/websites', [ApiWebsiteController::class, 'index']);
    Route::post('/websites', [ApiWebsiteController::class, 'store']);
    Route::get('/websites/{website}', [ApiWebsiteController::class, 'show']);
    Route::put('/websites/{website}', [ApiWebsiteController::class, 'update']);
    Route::delete('/websites/{website}', [ApiWebsiteController::class, 'destroy']);

    // 数据查询
    Route::get('/websites/{website}/realtime', [AnalyticsController::class, 'realtime']);
    Route::get('/websites/{website}/visitors', [AnalyticsController::class, 'visitors']);
    Route::get('/websites/{website}/events', [AnalyticsController::class, 'events']);
    Route::get('/websites/{website}/metrics', [AnalyticsController::class, 'metrics']);
    Route::get('/websites/{website}/top-pages', [AnalyticsController::class, 'topPages']);
    Route::get('/websites/{website}/top-referrers', [AnalyticsController::class, 'topReferrers']);
    Route::get('/websites/{website}/top-countries', [AnalyticsController::class, 'topCountries']);
    Route::get('/websites/{website}/top-browsers', [AnalyticsController::class, 'topBrowsers']);
    Route::get('/websites/{website}/top-devices', [AnalyticsController::class, 'topDevices']);
    Route::get('/websites/{website}/top-operating-systems', [AnalyticsController::class, 'topOperatingSystems']);
    Route::get('/websites/{website}/sessions', [AnalyticsController::class, 'sessions']);
    Route::get('/websites/{website}/goals', [AnalyticsController::class, 'goals']);
    Route::get('/websites/{website}/utm', [AnalyticsController::class, 'utm']);

    // 目标 CRUD（规格书 §8：/api/goals）
    Route::get('/websites/{website}/goals/list', [ResourcesController::class, 'goalsIndex']);
    Route::post('/websites/{website}/goals', [ResourcesController::class, 'goalsStore']);
    Route::get('/websites/{website}/goals/{goal}/detail', [ResourcesController::class, 'goalsShow']);
    Route::put('/websites/{website}/goals/{goal}', [ResourcesController::class, 'goalsUpdate']);
    Route::delete('/websites/{website}/goals/{goal}', [ResourcesController::class, 'goalsDestroy']);

    // 热图 CRUD（规格书 §8：/api/heatmaps）
    Route::get('/websites/{website}/heatmaps', [WebsiteDataController::class, 'heatmapsIndex']);
    Route::post('/websites/{website}/heatmaps', [WebsiteDataController::class, 'heatmapsStore']);
    Route::put('/websites/{website}/heatmaps/{heatmap}', [WebsiteDataController::class, 'heatmapsUpdate']);
    Route::delete('/websites/{website}/heatmaps/{heatmap}', [WebsiteDataController::class, 'heatmapsDestroy']);

    // 事件子项 / 出站点击（规格书 §8：/api/events-children、/api/outbound-clicks）
    Route::get('/websites/{website}/events-children', [WebsiteDataController::class, 'eventChildrenIndex']);
    Route::get('/websites/{website}/outbound-clicks', [WebsiteDataController::class, 'outboundClicksIndex']);
});

// 公开 API（需要 API Key）
Route::prefix('v1/public')->middleware('throttle:60,1')->group(function (): void {
    Route::post('/track', [\App\Http\Controllers\Api\PublicTrackerController::class, 'track']);
});