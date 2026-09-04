<?php

use App\Http\Controllers\Api\PublicTrackerController;
use App\Http\Controllers\Api\v1\AccountController;
use App\Http\Controllers\Api\v1\AdminApiController;
use App\Http\Controllers\Api\v1\AnalyticsController;
use App\Http\Controllers\Api\v1\ApiAnnotationsController;
use App\Http\Controllers\Api\v1\ApiEventsChildrenController;
use App\Http\Controllers\Api\v1\ApiGoalsConversionsController;
use App\Http\Controllers\Api\v1\ApiLogsController;
use App\Http\Controllers\Api\v1\ApiOutboundClicksController;
use App\Http\Controllers\Api\v1\ApiPaymentsController;
use App\Http\Controllers\Api\v1\ApiReplaysController;
use App\Http\Controllers\Api\v1\ApiSessionsController;
use App\Http\Controllers\Api\v1\ApiTeamMembersController;
use App\Http\Controllers\Api\v1\ApiVisitorsController;
use App\Http\Controllers\Api\v1\DomainController;
use App\Http\Controllers\Api\v1\ResourcesController;
use App\Http\Controllers\Api\v1\WebsiteController as ApiWebsiteController;
use App\Http\Controllers\Api\v1\WebsiteDataController;
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
    Route::get('/user', [AccountController::class, 'user'])->name('api.v1.user');
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

    // 数据查询（路由级所有权校验 · 安全审计周期 #19：can:own,website 为第一道
// 防线，控制器内 authorizeWebsite 保留作纵深防御——新增方法不再可能漏检）
Route::middleware('can:own,website')->group(function (): void {
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

    // 统计聚合 / 双模式页面浏览 / 回放（规格书 §8：statistics、pageviews-*、replays）
    Route::get('/websites/{website}/statistics', [AnalyticsController::class, 'statistics']);
    Route::get('/websites/{website}/pageviews-advanced', [AnalyticsController::class, 'pageviewsAdvanced']);
    Route::get('/websites/{website}/pageviews-lightweight', [AnalyticsController::class, 'pageviewsLightweight']);
    Route::get('/websites/{website}/replays', [AnalyticsController::class, 'replays']);
});

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

    // 套餐只读（规格书 §8：/api/plans）
    Route::get('/plans', [AccountController::class, 'plans']);

    // ========================================
    // 新增 API v1 端点（规格书 §8 扩展）
    // ========================================

    // 标注 CRUD（独立控制器版本，规格书 §8）
    Route::get('/websites/{website}/annotations', [ApiAnnotationsController::class, 'index']);
    Route::post('/websites/{website}/annotations', [ApiAnnotationsController::class, 'store']);
    Route::put('/websites/{website}/annotations/{annotation}', [ApiAnnotationsController::class, 'update']);
    Route::delete('/websites/{website}/annotations/{annotation}', [ApiAnnotationsController::class, 'destroy']);

    // 事件子项（独立控制器版本，规格书 §8）
    Route::get('/websites/{website}/events-children/list', [ApiEventsChildrenController::class, 'index']);

    // 目标转化（规格书 §8：/api/goals-conversions）
    Route::get('/websites/{website}/goals-conversions', [ApiGoalsConversionsController::class, 'index']);
    Route::get('/websites/{website}/goals-conversions/{conversionId}', [ApiGoalsConversionsController::class, 'show']);

    // 操作日志（独立控制器版本，规格书 §8）
    Route::get('/logs/list', [ApiLogsController::class, 'index']);

    // 出站点击（独立控制器版本，规格书 §8）
    Route::get('/websites/{website}/outbound-clicks/list', [ApiOutboundClicksController::class, 'index']);

    // 支付记录（独立控制器版本，规格书 §8）
    Route::get('/payments/list', [ApiPaymentsController::class, 'index']);

    // 会话回放（独立控制器版本，规格书 §8）
    Route::get('/websites/{website}/replays/list', [ApiReplaysController::class, 'index']);
    Route::get('/websites/{website}/replays/{replayId}', [ApiReplaysController::class, 'show']);

    // 会话（独立控制器版本，规格书 §8）
    Route::get('/websites/{website}/sessions/list', [ApiSessionsController::class, 'index']);
    Route::get('/websites/{website}/sessions/{sessionId}', [ApiSessionsController::class, 'show']);

    // 团队成员（独立控制器版本，规格书 §8）
    Route::get('/teams/{team}/members/list', [ApiTeamMembersController::class, 'index']);
    Route::get('/teams/{team}/members/{memberId}', [ApiTeamMembersController::class, 'show']);
    Route::delete('/teams/{team}/members/{memberId}', [ApiTeamMembersController::class, 'destroy']);

    // 访客（规格书 §8：/api/visitors）
    Route::get('/websites/{website}/visitors/list', [ApiVisitorsController::class, 'index']);
    Route::get('/websites/{website}/visitors/{visitorId}', [ApiVisitorsController::class, 'show']);
});

// 公开 API（需要 API Key）
Route::prefix('v1/public')->middleware('throttle:60,1,api-public')->group(function (): void {
    Route::post('/track', [PublicTrackerController::class, 'track']);
});

// ========================================
// Admin API 路由（规格书 §7：admin-api 路由组）
// 认证方式: Bearer Token (admin api_key) + admin 中间件
// 注意：不用 auth:api —— 项目未定义 api guard（config/auth.php 仅 web），
// api.key 中间件已校验 Bearer 并 auth('web')->login()，AdminMiddleware
// 依赖默认 guard 检查认证与 type=1，语义完整
// ========================================
Route::prefix('v1/admin')->middleware(['api.key', 'admin'])->group(function (): void {
    // 系统状态
    Route::get('/status', function () {
        return response()->json([
            'version' => config('app.version', '55.0.0'),
            'php_version' => PHP_VERSION,
            'database' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
        ]);
    });

    // 用户管理（规格书 §6.3.2）
    Route::get('/users', [AdminApiController::class, 'users']);
    Route::post('/users', [AdminApiController::class, 'createUser']);
    Route::get('/users/{userId}', [AdminApiController::class, 'getUser']);
    Route::put('/users/{userId}', [AdminApiController::class, 'updateUser']);
    Route::delete('/users/{userId}', [AdminApiController::class, 'deleteUser']);

    // 网站管理（规格书 §6.3.2）
    Route::get('/websites', [AdminApiController::class, 'websites']);
    Route::get('/websites/{websiteId}', [AdminApiController::class, 'getWebsite']);
    Route::put('/websites/{websiteId}', [AdminApiController::class, 'updateWebsite']);
    Route::delete('/websites/{websiteId}', [AdminApiController::class, 'deleteWebsite']);

    // 套餐管理（规格书 §6.3.3）
    Route::get('/plans', [AdminApiController::class, 'plans']);
    Route::post('/plans', [AdminApiController::class, 'createPlan']);
    Route::put('/plans/{planId}', [AdminApiController::class, 'updatePlan']);
    Route::delete('/plans/{planId}', [AdminApiController::class, 'deletePlan']);

    // 支付记录（规格书 §6.3.3）
    Route::get('/payments', [AdminApiController::class, 'payments']);
    Route::get('/payments/{paymentId}', [AdminApiController::class, 'getPayment']);

    // 系统设置（规格书 §6.3.1）
    Route::get('/settings', [AdminApiController::class, 'getSettings']);
    Route::put('/settings', [AdminApiController::class, 'updateSettings']);

    // 统计数据（规格书 §6.3.5）
    Route::get('/statistics', [AdminApiController::class, 'getStatistics']);

    // 插件管理（规格书 §14）
    Route::get('/plugins', [AdminApiController::class, 'plugins']);
    Route::put('/plugins/{pluginId}', [AdminApiController::class, 'updatePlugin']);
});
