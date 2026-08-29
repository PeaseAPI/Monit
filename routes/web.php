<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AdminBlogPosts;
use App\Http\Controllers\AdminBroadcasts;
use App\Http\Controllers\AdminCodes;
use App\Http\Controllers\AdminDomains;
use App\Http\Controllers\AdminIndex;
use App\Http\Controllers\AdminLicense;
use App\Http\Controllers\AdminNotifications;
use App\Http\Controllers\AdminPages;
use App\Http\Controllers\AdminPayments;
use App\Http\Controllers\AdminPlans;
use App\Http\Controllers\AdminPlugins;
use App\Http\Controllers\AdminSettings;
use App\Http\Controllers\AdminStatistics;
use App\Http\Controllers\AdminTaxes;
use App\Http\Controllers\AdminUsers;
use App\Http\Controllers\AdminWebsites;
use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\InternalNotificationsController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PixelTrackController;
use App\Http\Controllers\PublicStatisticsController;
use App\Http\Controllers\ReplayController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsitesImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Monit 路由表（依据规格书 §2）
|--------------------------------------------------------------------------
*/

// ========================================
// 像素采集端点（公开，CORS 全开）
// ========================================
Route::match(['get', 'post'], '/pixel-track/{pixel_key}', PixelTrackController::class)
    ->name('pixel.track');
Route::options('/pixel-track/{pixel_key}', [PixelTrackController::class, 'preflight']);

// 支付网关 Webhook（无需 CSRF，外部服务回调）
Route::post('/webhooks/stripe', [PaymentController::class, 'stripeWebhook'])->name('webhooks.stripe');
Route::post('/webhooks/paypal', [PaymentController::class, 'paypalWebhook'])->name('webhooks.paypal');

// ========================================
// 前台公开页面（规格书 §6.1）
// ========================================
Route::get('/', [IndexController::class, 'index'])->name('index');
Route::get('/blog', [IndexController::class, 'blog'])->name('blog');
Route::get('/blog/{url}', [IndexController::class, 'blogPost'])->name('blog.post');
Route::get('/page/{url}', [IndexController::class, 'page'])->name('page');
Route::get('/help', [IndexController::class, 'help'])->name('help');
Route::get('/contact', [IndexController::class, 'contact'])->name('contact');
Route::post('/contact', [IndexController::class, 'contactSend'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/plan', [IndexController::class, 'plan'])->name('plan');
Route::get('/api/docs', [IndexController::class, 'apiDocs'])->name('api.docs');

// 站点地图 / Cookie 同意 / 退订 / 维护页（规格书 §6.1）
Route::get('/sitemap', [IndexController::class, 'sitemap'])->name('sitemap');
Route::post('/cookie-consent', [IndexController::class, 'cookieConsent'])->name('cookie.consent');
Route::get('/unsubscribe', [IndexController::class, 'unsubscribe'])->name('unsubscribe');
Route::get('/maintenance', [IndexController::class, 'maintenance'])->name('maintenance');

// 公开统计页（规格书 §6.2.2：/statistics/{key}）
Route::get('/statistics/{pixel_key}', [PublicStatisticsController::class, 'show'])->name('statistics.public');
Route::post('/statistics/{pixel_key}/auth', [PublicStatisticsController::class, 'authenticate'])->name('statistics.public.auth');

// ========================================
// 访客态路由（登录 / 注册 / 密码重置 / 邮箱激活）
// ========================================
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // 两步验证（规格书 §12.4）
    Route::get('/login/twofa', [AuthController::class, 'showTwoFactor'])->name('login.twofa');
    Route::post('/login/twofa', [AuthController::class, 'verifyTwoFactor'])->middleware('throttle:10,1')->name('login.twofa.verify');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');

            // 密码重置（规格书 §6.1）
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:5,1')->name('password.email');

    // 社交登录（规格书 §12.3：Google + GitHub MVP）
    Route::get('/social-login/{provider}', [SocialLoginController::class, 'redirect'])->name('social-login.redirect');
    Route::get('/social-login/callback/{provider}', [SocialLoginController::class, 'callback'])->name('social-login.callback');
    Route::get('/reset-password/{code}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

    // 邮箱激活（规格书 §6.1）
    Route::get('/activate-user/{code}', [ActivationController::class, 'activate'])->name('activation.activate');
    Route::get('/resend-activation', [ActivationController::class, 'showResendForm'])->name('activation.resend');
    Route::post('/resend-activation', [ActivationController::class, 'resend'])->middleware('throttle:3,1')->name('activation.resend.post');
    Route::get('/sent-activation', [ActivationController::class, 'sent'])->name('activation.sent');
});

// ========================================
// 用户中心路由（需要登录 auth）
// ========================================
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // 仪表盘
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/install/{website}', [DashboardController::class, 'install'])
        ->middleware('can:own,website')->name('dashboard.install');

            // 账号管理
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/account/logs', [AccountController::class, 'logs'])->name('account.logs');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->name('account.update-password');
    Route::put('/account/api-key', [AccountController::class, 'regenerateApiToken'])->name('account.regenerate_api_key');
    Route::delete('/account/api-key', [AccountController::class, 'revokeApiToken'])->name('account.revoke_api_key');
    Route::get('/account/twofa/setup', [AccountController::class, 'twofaSetup'])->name('account.twofa.setup');
    Route::post('/account/twofa/enable', [AccountController::class, 'twofaEnable'])->name('account.twofa.enable');
    Route::delete('/account/twofa', [AccountController::class, 'twofaDisable'])->name('account.twofa.disable');
    Route::delete('/account', [AccountController::class, 'destroy'])->name('account.destroy');

    // 网站管理
    Route::get('/websites', [WebsiteController::class, 'index'])->name('websites.index');
    Route::get('/websites/create', [WebsiteController::class, 'create'])->name('websites.create');
        Route::post('/websites', [WebsiteController::class, 'store'])->middleware('plan_limit:websites_limit')->name('websites.store');
    Route::get('/websites/import', [WebsitesImportController::class, 'index'])->name('websites.import');
    Route::post('/websites/import', [WebsitesImportController::class, 'store'])->middleware('plan_limit:websites_limit')->name('websites.import.store');
    Route::get('/websites/{website}/edit', [WebsiteController::class, 'edit'])
        ->middleware('can:own,website')->name('websites.edit');
    Route::put('/websites/{website}', [WebsiteController::class, 'update'])
        ->middleware('can:own,website')->name('websites.update');
    Route::delete('/websites/{website}', [WebsiteController::class, 'destroy'])
        ->middleware('can:own,website')->name('websites.destroy');

                // 数据统计
    Route::get('/stats/{website}/overview', [StatsController::class, 'overview'])
        ->middleware('can:own,website')->name('stats.overview');

    // 实时在线（规格书 §6.2.1：/realtime）
    Route::get('/stats/{website}/realtime', [StatsController::class, 'realtime'])
        ->middleware('can:own,website')->name('stats.realtime');
    Route::get('/stats/{website}/realtime/data', [StatsController::class, 'realtimeData'])
        ->middleware('can:own,website')->name('stats.realtime.data');
    Route::get('/stats/{website}', [StatsController::class, 'index'])
        ->middleware('can:own,website')->name('stats.index');
    Route::get('/stats/{website}/visitors', [StatsController::class, 'visitors'])
        ->middleware('can:own,website')->name('stats.visitors');
    Route::get('/stats/{website}/referrers', [StatsController::class, 'referrers'])
        ->middleware('can:own,website')->name('stats.referrers');
    Route::get('/stats/{website}/outbound-clicks', [StatsController::class, 'outboundClicks'])
        ->middleware('can:own,website')->name('stats.outbound_clicks');
    Route::get('/stats/{website}/events', [StatsController::class, 'events'])
        ->middleware('can:own,website')->name('stats.events');
    Route::get('/stats/{website}/top-pages', [StatsController::class, 'topPages'])
        ->middleware('can:own,website')->name('stats.top_pages');
    Route::get('/stats/{website}/top-referrers', [StatsController::class, 'topReferrers'])
        ->middleware('can:own,website')->name('stats.top_referrers');
    Route::get('/stats/{website}/top-countries', [StatsController::class, 'topCountries'])
        ->middleware('can:own,website')->name('stats.top_countries');
    Route::get('/stats/{website}/top-browsers', [StatsController::class, 'topBrowsers'])
        ->middleware('can:own,website')->name('stats.top_browsers');
    Route::get('/stats/{website}/top-devices', [StatsController::class, 'topDevices'])
        ->middleware('can:own,website')->name('stats.top_devices');
    Route::get('/stats/{website}/top-operating-systems', [StatsController::class, 'topOperatingSystems'])
        ->middleware('can:own,website')->name('stats.top_os');

    // 目标转化
    Route::get('/stats/{website}/goals', [GoalController::class, 'index'])
        ->middleware('can:own,website')->name('stats.goals');
    Route::get('/stats/{website}/goals/create', [GoalController::class, 'create'])
        ->middleware('can:own,website')->name('stats.goals.create');
        Route::post('/stats/goals', [GoalController::class, 'store'])->middleware('plan_limit:websites_goals_limit')->name('stats.goals.store');
    Route::put('/stats/goals', [GoalController::class, 'update'])->name('stats.goals.update');
    Route::delete('/stats/goals/{goalId}', [GoalController::class, 'delete'])->name('stats.goals.delete');

    // 图表标注
    Route::get('/stats/{website}/annotations', [AnnotationController::class, 'index'])
        ->middleware('can:own,website')->name('stats.annotations');
    Route::get('/stats/{website}/annotations/create', [AnnotationController::class, 'create'])
        ->middleware('can:own,website')->name('stats.annotations.create');
        Route::post('/stats/annotations', [AnnotationController::class, 'store'])->middleware('plan_limit:annotations_limit')->name('stats.annotations.store');
    Route::put('/stats/annotations', [AnnotationController::class, 'update'])->name('stats.annotations.update');
    Route::delete('/stats/annotations/{annotationId}', [AnnotationController::class, 'delete'])->name('stats.annotations.delete');

    // 热图
    Route::get('/stats/{website}/heatmaps', [HeatmapController::class, 'index'])
        ->middleware('can:own,website')->name('stats.heatmaps');
    Route::get('/stats/{website}/heatmaps/create', [HeatmapController::class, 'create'])
        ->middleware('can:own,website')->name('stats.heatmaps.create');
        Route::post('/stats/heatmaps', [HeatmapController::class, 'store'])->middleware('plan_limit:websites_heatmaps_limit')->name('stats.heatmaps.store');
    Route::get('/stats/{website}/heatmaps/{heatmapId}', [HeatmapController::class, 'show'])
        ->middleware('can:own,website')->name('stats.heatmaps.show');
    Route::put('/stats/heatmaps', [HeatmapController::class, 'update'])->name('stats.heatmaps.update');
    Route::delete('/stats/heatmaps/{heatmapId}', [HeatmapController::class, 'destroy'])->name('stats.heatmaps.destroy');

    // 会话回放
    Route::get('/stats/{website}/replays', [ReplayController::class, 'index'])
        ->middleware('can:own,website')->name('stats.replays');
    Route::get('/stats/{website}/replays/{replayId}', [ReplayController::class, 'show'])
        ->middleware('can:own,website')->name('stats.replays.show');

    // 自定义域名
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::get('/domains/create', [DomainController::class, 'create'])->name('domains.create');
        Route::post('/domains', [DomainController::class, 'store'])->middleware('plan_limit:domains_limit')->name('domains.store');
    Route::put('/domains', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{domainId}', [DomainController::class, 'destroy'])->name('domains.destroy');

    // 团队协作
    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{teamId}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/teams/invite', [TeamController::class, 'invite'])->name('teams.invite');
    Route::put('/teams/accept/{memberId}', [TeamController::class, 'accept'])->name('teams.accept');
    Route::delete('/teams/members/{memberId}', [TeamController::class, 'remove'])->name('teams.remove');
    Route::delete('/teams/{teamId}', [TeamController::class, 'destroy'])->name('teams.destroy');

    // 内部通知
    Route::get('/notifications', [InternalNotificationsController::class, 'index'])->name('notifications.index');
    Route::put('/notifications/{notificationId}/read', [InternalNotificationsController::class, 'markAsRead'])->name('notifications.read');
    Route::put('/notifications/read-all', [InternalNotificationsController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('/notifications/{notificationId}', [InternalNotificationsController::class, 'destroy'])->name('notifications.destroy');

                // 推荐返佣
    Route::get('/referrals', [AffiliateController::class, 'index'])->name('referrals.index');
    Route::post('/referrals/withdrawal', [AffiliateController::class, 'requestWithdrawal'])->name('referrals.withdrawal');
    Route::get('/referrals/withdrawals', [AffiliateController::class, 'withdrawals'])->name('referrals.withdrawals');

        // 支付（规格书 §10/§11）
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::get('/payments/redeem-code', [PaymentController::class, 'showRedeemCode'])->name('payments.redeem');
    Route::post('/payments/redeem-code', [PaymentController::class, 'redeemCode'])->name('payments.redeem.submit');
    Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof'])->name('payments.proof');
    Route::get('/payments/history', [PaymentController::class, 'history'])->name('payments.history');
});

// ========================================
// Admin 管理后台路由（需要 admin 中间件）
// ========================================
Route::middleware(['auth', 'admin'])->group(function (): void {

    // 管理概览
    Route::get('/admin', [AdminIndex::class, 'index'])->name('admin.index');
    Route::get('/admin/statistics', [AdminStatistics::class, 'index'])->name('admin.statistics');
    Route::get('/admin/settings', [AdminSettings::class, 'index'])->name('admin.settings.index');
    Route::put('/admin/settings', [AdminSettings::class, 'update'])->name('admin.settings.update');

    // 授权许可（规格书 §15.2：Ed25519 离线 License）
    Route::get('/admin/license', [AdminLicense::class, 'index'])->name('admin.license.index');
    Route::post('/admin/license', [AdminLicense::class, 'upload'])->name('admin.license.upload');
    Route::get('/admin/license/refresh', [AdminLicense::class, 'refresh'])->name('admin.license.refresh');

    // 插件管理（规格书 §14：install → activate → deactivate → uninstall）
    Route::get('/admin/plugins', [AdminPlugins::class, 'index'])->name('admin.plugins.index');
    Route::post('/admin/plugins/{plugin}/install', [AdminPlugins::class, 'install'])->name('admin.plugins.install');
    Route::post('/admin/plugins/{plugin}/activate', [AdminPlugins::class, 'activate'])->name('admin.plugins.activate');
    Route::put('/admin/plugins/{plugin}/deactivate', [AdminPlugins::class, 'deactivate'])->name('admin.plugins.deactivate');
    Route::delete('/admin/plugins/{plugin}', [AdminPlugins::class, 'uninstall'])->name('admin.plugins.uninstall');
    Route::post('/admin/plugins/{plugin}/settings', [AdminPlugins::class, 'updateSettings'])->name('admin.plugins.settings');

        // 用户管理
    Route::get('/admin/users', [AdminUsers::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUsers::class, 'create'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUsers::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/logs', [AdminUsers::class, 'logs'])->name('admin.users.logs');
    Route::get('/admin/users/{userId}/edit', [AdminUsers::class, 'edit'])->name('admin.users.edit');
    Route::put('/admin/users/{userId}', [AdminUsers::class, 'update'])->name('admin.users.update');
    Route::get('/admin/users/{userId}', [AdminUsers::class, 'view'])->name('admin.users.view');
    Route::put('/admin/users/{userId}/toggle-status', [AdminUsers::class, 'toggleStatus'])->name('admin.users.toggle_status');

    // 网站管理
    Route::get('/admin/websites', [AdminWebsites::class, 'index'])->name('admin.websites.index');
    Route::put('/admin/websites/{websiteId}/toggle-status', [AdminWebsites::class, 'toggleStatus'])->name('admin.websites.toggle_status');

    // 域名管理
    Route::get('/admin/domains', [AdminDomains::class, 'index'])->name('admin.domains.index');
    Route::put('/admin/domains/{domainId}/toggle-status', [AdminDomains::class, 'toggleStatus'])->name('admin.domains.toggle_status');

    // 套餐管理
    Route::get('/admin/plans', [AdminPlans::class, 'index'])->name('admin.plans.index');
    Route::get('/admin/plans/create', [AdminPlans::class, 'create'])->name('admin.plans.create');
    Route::post('/admin/plans', [AdminPlans::class, 'store'])->name('admin.plans.store');
    Route::get('/admin/plans/{planId}/edit', [AdminPlans::class, 'edit'])->name('admin.plans.edit');
    Route::put('/admin/plans/{planId}', [AdminPlans::class, 'update'])->name('admin.plans.update');
    Route::put('/admin/plans/{planId}/toggle-status', [AdminPlans::class, 'toggleStatus'])->name('admin.plans.toggle_status');

    // 支付管理
    Route::get('/admin/payments', [AdminPayments::class, 'index'])->name('admin.payments.index');
    Route::get('/admin/payments/create', [AdminPayments::class, 'create'])->name('admin.payments.create');
    Route::post('/admin/payments', [AdminPayments::class, 'store'])->name('admin.payments.store');
    Route::get('/admin/payments/{paymentId}', [AdminPayments::class, 'view'])->name('admin.payments.view');

        // 税费管理
    Route::get('/admin/taxes', [AdminTaxes::class, 'index'])->name('admin.taxes.index');
    Route::get('/admin/taxes/create', [AdminTaxes::class, 'create'])->name('admin.taxes.create');
    Route::post('/admin/taxes', [AdminTaxes::class, 'store'])->name('admin.taxes.store');
    Route::get('/admin/taxes/{taxId}/edit', [AdminTaxes::class, 'edit'])->name('admin.taxes.edit');
    Route::put('/admin/taxes/{taxId}', [AdminTaxes::class, 'update'])->name('admin.taxes.update');
    Route::delete('/admin/taxes/{taxId}', [AdminTaxes::class, 'destroy'])->name('admin.taxes.destroy');

    // 联盟提现管理（规格书 §6.3 / §14.7）
    Route::get('/admin/affiliates-withdrawals', [AdminPayments::class, 'affiliateWithdrawals'])->name('admin.affiliates-withdrawals.index');
    Route::put('/admin/affiliates-withdrawals/{withdrawalId}/approve', [AdminPayments::class, 'approveWithdrawal'])->name('admin.affiliates-withdrawals.approve');
    Route::put('/admin/affiliates-withdrawals/{withdrawalId}/reject', [AdminPayments::class, 'rejectWithdrawal'])->name('admin.affiliates-withdrawals.reject');

    // 兑换码管理（规格书 §6.3.3 / §10.3：AdminCodes / AdminRedeemedCodes）
    Route::get('/admin/codes', [AdminCodes::class, 'index'])->name('admin.codes.index');
    Route::get('/admin/codes/create', [AdminCodes::class, 'create'])->name('admin.codes.create');
    Route::post('/admin/codes', [AdminCodes::class, 'store'])->name('admin.codes.store');
    Route::get('/admin/codes/redeemed', [AdminCodes::class, 'redeemed'])->name('admin.codes.redeemed');
    Route::get('/admin/codes/{codeId}/edit', [AdminCodes::class, 'edit'])->name('admin.codes.edit');
    Route::put('/admin/codes/{codeId}', [AdminCodes::class, 'update'])->name('admin.codes.update');
    Route::delete('/admin/codes/{codeId}', [AdminCodes::class, 'destroy'])->name('admin.codes.destroy');

    // 博客文章管理（规格书 §6.3.4：AdminBlogPosts）
    Route::get('/admin/blog-posts', [AdminBlogPosts::class, 'index'])->name('admin.blog-posts.index');
    Route::get('/admin/blog-posts/create', [AdminBlogPosts::class, 'create'])->name('admin.blog-posts.create');
    Route::post('/admin/blog-posts', [AdminBlogPosts::class, 'store'])->name('admin.blog-posts.store');
    Route::get('/admin/blog-posts/{postId}/edit', [AdminBlogPosts::class, 'edit'])->name('admin.blog-posts.edit');
    Route::put('/admin/blog-posts/{postId}', [AdminBlogPosts::class, 'update'])->name('admin.blog-posts.update');
    Route::put('/admin/blog-posts/{postId}/toggle-publish', [AdminBlogPosts::class, 'togglePublish'])->name('admin.blog-posts.toggle-publish');
    Route::delete('/admin/blog-posts/{postId}', [AdminBlogPosts::class, 'destroy'])->name('admin.blog-posts.destroy');

    // CMS 页面管理（规格书 §6.3.4：AdminPages）
    Route::get('/admin/pages', [AdminPages::class, 'index'])->name('admin.pages.index');
    Route::get('/admin/pages/create', [AdminPages::class, 'create'])->name('admin.pages.create');
    Route::post('/admin/pages', [AdminPages::class, 'store'])->name('admin.pages.store');
    Route::get('/admin/pages/{pageId}/edit', [AdminPages::class, 'edit'])->name('admin.pages.edit');
    Route::put('/admin/pages/{pageId}', [AdminPages::class, 'update'])->name('admin.pages.update');
    Route::delete('/admin/pages/{pageId}', [AdminPages::class, 'destroy'])->name('admin.pages.destroy');

    // 广播管理（规格书 §6.3.4：AdminBroadcasts，实际发送由 §13 cron 处理）
    Route::get('/admin/broadcasts', [AdminBroadcasts::class, 'index'])->name('admin.broadcasts.index');
    Route::get('/admin/broadcasts/create', [AdminBroadcasts::class, 'create'])->name('admin.broadcasts.create');
    Route::post('/admin/broadcasts', [AdminBroadcasts::class, 'store'])->name('admin.broadcasts.store');
    Route::get('/admin/broadcasts/{broadcastId}/edit', [AdminBroadcasts::class, 'edit'])->name('admin.broadcasts.edit');
    Route::put('/admin/broadcasts/{broadcastId}', [AdminBroadcasts::class, 'update'])->name('admin.broadcasts.update');
    Route::put('/admin/broadcasts/{broadcastId}/send', [AdminBroadcasts::class, 'send'])->name('admin.broadcasts.send');
    Route::delete('/admin/broadcasts/{broadcastId}', [AdminBroadcasts::class, 'destroy'])->name('admin.broadcasts.destroy');

    // 站内通知管理（规格书 §6.3.4：AdminInternalNotifications）
    Route::get('/admin/notifications', [AdminNotifications::class, 'index'])->name('admin.notifications.index');
    Route::get('/admin/notifications/create', [AdminNotifications::class, 'create'])->name('admin.notifications.create');
    Route::post('/admin/notifications', [AdminNotifications::class, 'store'])->name('admin.notifications.store');
    Route::delete('/admin/notifications/{notificationId}', [AdminNotifications::class, 'destroy'])->name('admin.notifications.destroy');
});
