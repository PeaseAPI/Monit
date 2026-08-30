<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountPaymentsController;
use App\Http\Controllers\AccountPlanController;
use App\Http\Controllers\AccountPreferencesController;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AdminAnnotations;
use App\Http\Controllers\AdminBlogPosts;
use App\Http\Controllers\AdminBlogPostsCategories;
use App\Http\Controllers\AdminBroadcasts;
use App\Http\Controllers\AdminCodes;
use App\Http\Controllers\AdminDomains;
use App\Http\Controllers\AdminHeatmaps;
use App\Http\Controllers\AdminIndex;
use App\Http\Controllers\AdminInvoice;
use App\Http\Controllers\AdminLanguages;
use App\Http\Controllers\AdminLicense;
use App\Http\Controllers\AdminLogs;
use App\Http\Controllers\AdminNotifications;
use App\Http\Controllers\AdminPages;
use App\Http\Controllers\AdminPagesCategories;
use App\Http\Controllers\AdminPayments;
use App\Http\Controllers\AdminPlans;
use App\Http\Controllers\AdminPlugins;
use App\Http\Controllers\AdminPushSubscribers;
use App\Http\Controllers\AdminReplays;
use App\Http\Controllers\AdminSettings;
use App\Http\Controllers\AdminStatistics;
use App\Http\Controllers\AdminTeams;
use App\Http\Controllers\AdminTaxes;
use App\Http\Controllers\AdminUsers;
use App\Http\Controllers\AdminWebsites;
use App\Http\Controllers\AnnotationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\FaviconController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\AccountApiController;
use App\Http\Controllers\AdminAffiliatesWithdrawals;
use App\Http\Controllers\AdminRedeemedCodes;
use App\Http\Controllers\AdminUserCreate;
use App\Http\Controllers\AdminUsersLogs;
use App\Http\Controllers\AdminUserUpdate;
use App\Http\Controllers\AdminUserView;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\InternalNotificationsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OutboundClicksController;
use App\Http\Controllers\PageviewsAdvancedController;
use App\Http\Controllers\PageviewsLightweightController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayBillingController;
use App\Http\Controllers\PayThankYouController;
use App\Http\Controllers\PixelTrackController;
use App\Http\Controllers\PublicStatisticsController;
use App\Http\Controllers\ReplayController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\DashboardViewController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebsitesImportController;
use App\Http\Controllers\WebhookMollieController;
use App\Http\Controllers\WebhookPaystackController;
use App\Http\Controllers\WebhookPaymentController;
use App\Http\Controllers\WebhookRazorpayController;
use App\Http\Controllers\SessionAjaxController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SpotlightController;
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

// 更多支付 Webhook 路由（规格书 §11：22 处理器）
Route::post('/webhooks/razorpay', WebhookRazorpayController::class)->name('webhooks.razorpay');
Route::post('/webhooks/mollie', WebhookMollieController::class)->name('webhooks.mollie');
Route::post('/webhooks/paystack', WebhookPaystackController::class)->name('webhooks.paystack');
Route::post('/webhooks/paddle', [WebhookPaymentController::class, 'paddle'])->name('webhooks.paddle');
Route::post('/webhooks/paddle-billing', [WebhookPaymentController::class, 'paddleBilling'])->name('webhooks.paddle-billing');
Route::post('/webhooks/mercadopago', [WebhookPaymentController::class, 'mercadopago'])->name('webhooks.mercadopago');
Route::post('/webhooks/midtrans', [WebhookPaymentController::class, 'midtrans'])->name('webhooks.midtrans');
Route::post('/webhooks/flutterwave', [WebhookPaymentController::class, 'flutterwave'])->name('webhooks.flutterwave');
Route::post('/webhooks/lemonsqueezy', [WebhookPaymentController::class, 'lemonsqueezy'])->name('webhooks.lemonsqueezy');
Route::post('/webhooks/yookassa', [WebhookPaymentController::class, 'yookassa'])->name('webhooks.yookassa');
Route::post('/webhooks/payu', [WebhookPaymentController::class, 'payu'])->name('webhooks.payu');
Route::post('/webhooks/iyzico', [WebhookPaymentController::class, 'iyzico'])->name('webhooks.iyzico');
Route::post('/webhooks/crypto', [WebhookPaymentController::class, 'crypto'])->name('webhooks.crypto');
Route::post('/webhooks/myfatoorah', [WebhookPaymentController::class, 'myfatoorah'])->name('webhooks.myfatoorah');
Route::post('/webhooks/klarna', [WebhookPaymentController::class, 'klarna'])->name('webhooks.klarna');
Route::post('/webhooks/plisio', [WebhookPaymentController::class, 'plisio'])->name('webhooks.plisio');
Route::post('/webhooks/revolut', [WebhookPaymentController::class, 'revolut'])->name('webhooks.revolut');
Route::post('/webhooks/onepay', [WebhookPaymentController::class, 'onepay'])->name('webhooks.onepay');
Route::post('/webhooks/wechatpay', [WebhookPaymentController::class, 'wechatPay'])->name('webhooks.wechatpay');
Route::post('/webhooks/alipay', [WebhookPaymentController::class, 'alipay'])->name('webhooks.alipay');

// ========================================
// 前台公开页面（规格书 §6.1）
// ========================================
Route::get('/', [IndexController::class, 'index'])->name('index');
Route::get('/blog', [IndexController::class, 'blog'])->name('blog');
Route::get('/blog/{url}', [IndexController::class, 'blogPost'])->name('blog.post');
Route::get('/page/{url}', [IndexController::class, 'page'])->name('page');
Route::get('/pages', [IndexController::class, 'pages'])->name('pages'); // 规格 §6.1：自定义页面索引
Route::get('/help', [IndexController::class, 'help'])->name('help');
Route::get('/affiliate', [IndexController::class, 'affiliate'])->name('affiliate'); // 规格 §6.1：联盟介绍（插件启用时）
Route::get('/contact', [IndexController::class, 'contact'])->name('contact');
Route::post('/contact', [IndexController::class, 'contactSend'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/plan', [IndexController::class, 'plan'])->name('plan');
Route::get('/api/docs', [IndexController::class, 'apiDocs'])->name('api.docs');
Route::get('/api-documentation', [IndexController::class, 'apiDocs'])->name('api.documentation'); // 规格 §6.1 别名

// 站点地图 / Cookie 同意 / 退订 / 维护页（规格书 §6.1）
Route::get('/sitemap', [IndexController::class, 'sitemap'])->name('sitemap');
Route::post('/cookie-consent', [IndexController::class, 'cookieConsent'])->name('cookie.consent');
Route::get('/unsubscribe', [IndexController::class, 'unsubscribe'])->name('unsubscribe');
Route::post('/unsubscribe', [IndexController::class, 'unsubscribePost'])->name('unsubscribe.post');
Route::get('/maintenance', [IndexController::class, 'maintenance'])->name('maintenance');

// 动态 Favicon（规格书 §6.1：/favicon）
Route::get('/favicon', FaviconController::class)->name('favicon');

// SSO 单点登录（规格书 §6.1：/sso）
Route::get('/sso', [SsoController::class, 'login'])->name('sso.login');
Route::post('/sso', [SsoController::class, 'login'])->name('sso.login.post');

// 公开统计页（规格书 §6.2.2：/statistics/{key}）
Route::get('/statistics/{pixel_key}', [PublicStatisticsController::class, 'show'])->name('statistics.public');
Route::post('/statistics/{pixel_key}/auth', [PublicStatisticsController::class, 'authenticate'])->name('statistics.public.auth');

// ========================================
// 短信验证码发送（M17 §12.5；guest/auth 通用，phone_bind 场景内部校验登录态）
// ========================================
Route::post('/sms/send', [SmsController::class, 'send'])
    ->middleware('throttle:3,1')
    ->name('sms.send');

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

    // 短信重置密码（M17 §12.5）
    Route::get('/reset-password-by-sms', [ForgotPasswordController::class, 'showResetSmsForm'])->name('password.reset_sms');
    Route::post('/reset-password-by-sms', [ForgotPasswordController::class, 'resetBySms'])->middleware('throttle:5,1')->name('password.reset_sms.post');

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
    Route::post('/account/phone/bind', [AccountController::class, 'phoneBind'])->name('account.phone.bind');
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

    // 会话 AJAX 详情（规格书 §6.2.2：/session-ajax）
    Route::get('/session-ajax/{sessionId}', [SessionAjaxController::class, 'show'])
        ->name('session.ajax');

    // 聚焦搜索（规格书 §6.2.1：Spotlight 全局搜索）
    Route::get('/spotlight', [SpotlightController::class, 'search'])
        ->name('spotlight.search');

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

    // 订阅管理（规格书 §6.2.6：/pay-billing）
    Route::get('/pay-billing', [PayBillingController::class, 'index'])->name('pay.billing');
    Route::post('/pay-billing/cancel', [PayBillingController::class, 'cancel'])->name('pay.billing.cancel');

    // 支付成功页（规格书 §6.2.6：/pay-thank-you）
    Route::get('/pay-thank-you', [PayThankYouController::class, 'index'])->name('pay.thank_you');

    // 账户支付记录（规格书 §6.2.5：/account-payments）
    Route::get('/account-payments', [AccountPaymentsController::class, 'index'])->name('account.payments');

    // 套餐管理（规格书 §6.2.5：/account-plan）
    Route::get('/account-plan', [AccountPlanController::class, 'index'])->name('account.plan');
    Route::post('/account-plan/redeem', [AccountPlanController::class, 'redeemCode'])->name('account.plan.redeem');

        // 偏好设置（规格书 §6.2.5：/account-preferences）
    Route::get('/account-preferences', [AccountPreferencesController::class, 'index'])->name('account.preferences');
    Route::put('/account-preferences', [AccountPreferencesController::class, 'update'])->name('account.preferences.update');

    // API 密钥管理（规格书 §6.2.5：/account-api）
    Route::get('/account-api', [AccountApiController::class, 'index'])->name('account-api.index');
    Route::put('/account-api/regenerate', [AccountApiController::class, 'regenerate'])->name('account-api.regenerate');
    Route::delete('/account-api/revoke', [AccountApiController::class, 'revoke'])->name('account-api.revoke');

    // 发票（规格书 §6.2.6：/invoice）
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{payment}/download', [InvoiceController::class, 'download'])->name('invoices.download');

        // 信用票据（规格书 §6.2.6：/credit-notes）
    Route::get('/credit-notes', [InvoiceController::class, 'creditNotes'])->name('credit-notes.index');

    // 仪表盘视图管理（规格书 §6.2.1：DashboardViews；§10.2：dashboard_views_limit 配额）
    Route::get('/dashboard-views', [DashboardViewController::class, 'index'])->name('dashboard-views.index');
    Route::post('/dashboard-views', [DashboardViewController::class, 'store'])->middleware('plan_limit:dashboard_views_limit')->name('dashboard-views.store');
    Route::put('/dashboard-views/{viewId}', [DashboardViewController::class, 'update'])->name('dashboard-views.update');
    Route::delete('/dashboard-views/{viewId}', [DashboardViewController::class, 'destroy'])->name('dashboard-views.destroy');

    // 网站AJAX数据（规格书 §6.2.3：/websites-ajax）
    Route::get('/websites-ajax', [WebsiteController::class, 'ajax'])->name('websites.ajax');

    // 团队AJAX数据（规格书 §6.2.4：/teams-ajax、/teams-associations-ajax）
    Route::get('/teams-ajax', [TeamController::class, 'ajax'])->name('teams.ajax');
    Route::get('/teams-associations-ajax', [TeamController::class, 'associationsAjax'])->name('teams.associations-ajax');

    // 访客详情（规格书 §6.2.2：/visitor）
    Route::get('/stats/{website}/visitors/{visitorId}', [StatsController::class, 'visitorDetail'])
        ->middleware('can:own,website')->name('stats.visitor');

    // 会话详情（规格书 §6.2.2：/session-ajax）
    Route::get('/stats/{website}/sessions/{sessionId}', [StatsController::class, 'sessionDetail'])
        ->middleware('can:own,website')->name('stats.session');

        // 热图AJAX（规格书 §6.2.2：/heatmaps-ajax）
        Route::get('/stats/{website}/heatmaps-ajax/{heatmapId}', [HeatmapController::class, 'ajax'])
        ->middleware('can:own,website')->name('stats.heatmaps.ajax');

    // 页面浏览 - 高级模式（规格书 §6.2.2：/pageviews-advanced）
    Route::get('/stats/{website}/pageviews-advanced', [PageviewsAdvancedController::class, 'index'])
        ->middleware('can:own,website')->name('stats.pageviews-advanced');

    // 页面浏览 - 轻量模式（规格书 §6.2.2：/pageviews-lightweight）
    Route::get('/stats/{website}/pageviews-lightweight', [PageviewsLightweightController::class, 'index'])
        ->middleware('can:own,website')->name('stats.pageviews-lightweight');

    // 出站点击统计（规格书 §6.2.2：/outbound-clicks）
    Route::get('/stats/{website}/outbound-clicks', [OutboundClicksController::class, 'index'])
        ->middleware('can:own,website')->name('stats.outbound-clicks');

    // 访客详情（规格书 §6.2.2：/visitor，独立控制器）
    Route::get('/stats/{website}/visitor/{visitorId}', [VisitorController::class, 'show'])
        ->middleware('can:own,website')->name('stats.visitor-show');

    // 目标 CRUD 路由（规格书 §6.2.2：Goals）
    Route::get('/stats/{website}/goals', [GoalController::class, 'index'])->middleware('can:own,website')->name('goals.index');
    Route::get('/stats/{website}/goals/create', [GoalController::class, 'create'])->middleware('can:own,website')->name('goals.create');
    Route::post('/stats/{website}/goals', [GoalController::class, 'store'])->middleware('can:own,website')->name('goals.store');
    Route::get('/stats/{website}/goals/{goal}/edit', [GoalController::class, 'edit'])->middleware('can:own,website')->name('goals.edit');
    Route::put('/stats/{website}/goals/{goal}', [GoalController::class, 'update'])->middleware('can:own,website')->name('goals.update');
    Route::delete('/stats/{website}/goals/{goal}', [GoalController::class, 'delete'])->middleware('can:own,website')->name('goals.destroy');

    // 热图 CRUD 路由（规格书 §6.2.2：Heatmaps）
    Route::get('/stats/{website}/heatmaps', [HeatmapController::class, 'index'])->middleware('can:own,website')->name('heatmaps.index');
    Route::get('/stats/{website}/heatmaps/create', [HeatmapController::class, 'create'])->middleware('can:own,website')->name('heatmaps.create');
    Route::post('/stats/{website}/heatmaps', [HeatmapController::class, 'store'])->middleware('can:own,website')->name('heatmaps.store');
    Route::get('/stats/{website}/heatmaps/{heatmap}', [HeatmapController::class, 'show'])->middleware('can:own,website')->name('heatmaps.show');
    Route::delete('/stats/{website}/heatmaps/{heatmap}', [HeatmapController::class, 'destroy'])->middleware('can:own,website')->name('heatmaps.destroy-direct');

    // 回放 CRUD 路由（规格书 §6.2.2：Replays）
    Route::get('/stats/{website}/replays', [ReplayController::class, 'index'])->middleware('can:own,website')->name('replays.index');
    Route::get('/stats/{website}/replays/{replay}', [ReplayController::class, 'show'])->middleware('can:own,website')->name('replays.show');

    // 标注 CRUD 路由（规格书 §6.2.2：Annotations）
    Route::get('/stats/{website}/annotations', [AnnotationController::class, 'index'])->middleware('can:own,website')->name('annotations.index');
    Route::post('/stats/{website}/annotations', [AnnotationController::class, 'store'])->middleware('can:own,website')->name('annotations.store');
    Route::put('/stats/{website}/annotations/{annotation}', [AnnotationController::class, 'update'])->middleware('can:own,website')->name('annotations.update');
    Route::delete('/stats/{website}/annotations/{annotation}', [AnnotationController::class, 'destroy'])->middleware('can:own,website')->name('annotations.destroy');

    // 账户删除（规格书 §6.2.5：/account-delete）
    Route::get('/account-delete', [AccountController::class, 'deleteForm'])->name('account.delete-form');
    Route::delete('/account-delete', [AccountController::class, 'destroy'])->name('account.delete');

    // 兑换码兑换（规格书 §6.2.5：/account-redeem-code）
    Route::get('/account-redeem-code', [AccountController::class, 'redeemCodeForm'])->name('account.redeem-code-form');
    Route::post('/account-redeem-code', [AccountController::class, 'redeemCodeSubmit'])->name('account.redeem-code');
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

    // 插件管理（规格书 §14：install → activate → deactivate → uninstall）
    Route::get('/admin/plugins', [AdminPlugins::class, 'index'])->name('admin.plugins.index');
    Route::post('/admin/plugins/{plugin}/install', [AdminPlugins::class, 'install'])->name('admin.plugins.install');
    Route::post('/admin/plugins/{plugin}/activate', [AdminPlugins::class, 'activate'])->name('admin.plugins.activate');
    Route::put('/admin/plugins/{plugin}/deactivate', [AdminPlugins::class, 'deactivate'])->name('admin.plugins.deactivate');
    Route::delete('/admin/plugins/{plugin}', [AdminPlugins::class, 'uninstall'])->name('admin.plugins.uninstall');
    Route::post('/admin/plugins/{plugin}/settings', [AdminPlugins::class, 'updateSettings'])->name('admin.plugins.settings');

            // 用户管理（规格书 §6.3.2：AdminUsers / AdminUserCreate / AdminUserUpdate / AdminUserView / AdminUsersLogs）
    Route::get('/admin/users', [AdminUsers::class, 'index'])->name('admin.users.index');
    Route::get('/admin/users/create', [AdminUserCreate::class, 'index'])->name('admin.users.create');
    Route::post('/admin/users', [AdminUserCreate::class, 'store'])->name('admin.users.store');
    Route::get('/admin/users/logs', [AdminUsersLogs::class, 'index'])->name('admin.users.logs');
    Route::get('/admin/users/{userId}/edit', [AdminUserUpdate::class, 'index'])->name('admin.users.edit');
    Route::put('/admin/users/{userId}', [AdminUserUpdate::class, 'update'])->name('admin.users.update');
    Route::get('/admin/users/{userId}', [AdminUserView::class, 'index'])->name('admin.users.view');
    Route::put('/admin/users/{userId}/toggle-status', [AdminUserUpdate::class, 'toggleStatus'])->name('admin.users.toggle_status');
    Route::post('/admin/users/{userId}/login-as', [AdminUserUpdate::class, 'loginAs'])->name('admin.users.login-as');

    // 网站管理
    Route::get('/admin/websites', [AdminWebsites::class, 'index'])->name('admin.websites.index');
    Route::put('/admin/websites/{websiteId}/toggle-status', [AdminWebsites::class, 'toggleStatus'])->name('admin.websites.toggle_status');

        // 域名管理（规格书 §6.3.2：AdminDomains / AdminDomainCreate / AdminDomainUpdate）
    Route::get('/admin/domains', [AdminDomains::class, 'index'])->name('admin.domains.index');
    Route::get('/admin/domains/create', [AdminDomains::class, 'create'])->name('admin.domains.create');
    Route::post('/admin/domains', [AdminDomains::class, 'store'])->name('admin.domains.store');
    Route::get('/admin/domains/{domainId}/edit', [AdminDomains::class, 'edit'])->name('admin.domains.edit');
    Route::put('/admin/domains/{domainId}', [AdminDomains::class, 'update'])->name('admin.domains.update');
    Route::delete('/admin/domains/{domainId}', [AdminDomains::class, 'destroy'])->name('admin.domains.destroy');
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

    // 税费批量导入（规格书 §6.3.3：/admin/taxes-import）
    Route::get('/admin/taxes-import', [AdminTaxes::class, 'importForm'])->name('admin.taxes.import');
    Route::post('/admin/taxes-import', [AdminTaxes::class, 'import'])->name('admin.taxes.import.submit');

        // 联盟提现管理（规格书 §6.3 / §14.7：AdminAffiliatesWithdrawals）
    Route::get('/admin/affiliates-withdrawals', [AdminAffiliatesWithdrawals::class, 'index'])->name('admin.affiliates-withdrawals.index');
    Route::put('/admin/affiliates-withdrawals/{withdrawalId}/approve', [AdminAffiliatesWithdrawals::class, 'approve'])->name('admin.affiliates-withdrawals.approve');
    Route::put('/admin/affiliates-withdrawals/{withdrawalId}/reject', [AdminAffiliatesWithdrawals::class, 'reject'])->name('admin.affiliates-withdrawals.reject');
    Route::post('/admin/affiliates-withdrawals/bulk', [AdminAffiliatesWithdrawals::class, 'bulkUpdate'])->name('admin.affiliates-withdrawals.bulk');

        // 兑换码管理（规格书 §6.3.3 / §10.3：AdminCodes / AdminRedeemedCodes）
    Route::get('/admin/codes', [AdminCodes::class, 'index'])->name('admin.codes.index');
    Route::get('/admin/codes/create', [AdminCodes::class, 'create'])->name('admin.codes.create');
    Route::post('/admin/codes', [AdminCodes::class, 'store'])->name('admin.codes.store');
    Route::get('/admin/codes/redeemed', [AdminRedeemedCodes::class, 'index'])->name('admin.codes.redeemed');
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
    Route::get('/admin/broadcasts/{broadcastId}', [AdminBroadcasts::class, 'show'])->name('admin.broadcasts.show');
    Route::post('/admin/broadcasts/{broadcastId}/duplicate', [AdminBroadcasts::class, 'duplicate'])->name('admin.broadcasts.duplicate');

    // 站内通知管理（规格书 §6.3.4：AdminInternalNotifications）
    Route::get('/admin/notifications', [AdminNotifications::class, 'index'])->name('admin.notifications.index');
    Route::get('/admin/notifications/create', [AdminNotifications::class, 'create'])->name('admin.notifications.create');
    Route::post('/admin/notifications', [AdminNotifications::class, 'store'])->name('admin.notifications.store');
    Route::delete('/admin/notifications/{notificationId}', [AdminNotifications::class, 'destroy'])->name('admin.notifications.destroy');

    // 发票 / 信用票据（规格书 §6.3.3：AdminInvoice、AdminCreditNotes）
    Route::get('/admin/payments/{paymentId}/invoice', [AdminInvoice::class, 'invoice'])->name('admin.payments.invoice');
    Route::get('/admin/payments/{paymentId}/credit-note', [AdminInvoice::class, 'creditNote'])->name('admin.payments.credit_note');

    // 内容分类（规格书 §6.3.4：AdminPagesCategories、AdminBlogPostsCategories）
    Route::get('/admin/pages-categories', [AdminPagesCategories::class, 'index'])->name('admin.pages-categories.index');
    Route::post('/admin/pages-categories', [AdminPagesCategories::class, 'store'])->name('admin.pages-categories.store');
    Route::put('/admin/pages-categories/{pageCategoryId}', [AdminPagesCategories::class, 'update'])->name('admin.pages-categories.update');
    Route::delete('/admin/pages-categories/{pageCategoryId}', [AdminPagesCategories::class, 'destroy'])->name('admin.pages-categories.destroy');
    Route::get('/admin/blog-posts-categories', [AdminBlogPostsCategories::class, 'index'])->name('admin.blog-posts-categories.index');
    Route::post('/admin/blog-posts-categories', [AdminBlogPostsCategories::class, 'store'])->name('admin.blog-posts-categories.store');
    Route::put('/admin/blog-posts-categories/{categoryId}', [AdminBlogPostsCategories::class, 'update'])->name('admin.blog-posts-categories.update');
    Route::delete('/admin/blog-posts-categories/{categoryId}', [AdminBlogPostsCategories::class, 'destroy'])->name('admin.blog-posts-categories.destroy');

    // 团队管理（规格书 §6.3.2：AdminTeams、AdminTeamMembers）
    Route::get('/admin/teams', [AdminTeams::class, 'index'])->name('admin.teams.index');
    Route::get('/admin/teams/{teamId}/members', [AdminTeams::class, 'members'])->name('admin.teams.members');
    Route::delete('/admin/teams/{teamId}', [AdminTeams::class, 'destroy'])->name('admin.teams.destroy');
    Route::delete('/admin/teams/{teamId}/members/{memberId}', [AdminTeams::class, 'destroyMember'])->name('admin.teams.members.destroy');

    // 平台级数据管理（规格书 §6.3.5：AdminAnnotations、AdminHeatmaps、AdminReplays）
    Route::get('/admin/annotations', [AdminAnnotations::class, 'index'])->name('admin.annotations.index');
    Route::delete('/admin/annotations/{annotationId}', [AdminAnnotations::class, 'destroy'])->name('admin.annotations.destroy');
    Route::get('/admin/heatmaps', [AdminHeatmaps::class, 'index'])->name('admin.heatmaps.index');
    Route::delete('/admin/heatmaps/{heatmapId}', [AdminHeatmaps::class, 'destroy'])->name('admin.heatmaps.destroy');
    Route::get('/admin/replays', [AdminReplays::class, 'index'])->name('admin.replays.index');
    Route::delete('/admin/replays/{replayId}', [AdminReplays::class, 'destroy'])->name('admin.replays.destroy');

        // 账户日志（规格书 §6.3.5：AdminLogs、AdminLog、AdminLogDownload）
    Route::get('/admin/logs', [AdminLogs::class, 'index'])->name('admin.logs.index');
    Route::get('/admin/logs/download', [AdminLogs::class, 'download'])->name('admin.logs.download');
    Route::get('/admin/logs/{logId}', [AdminLogs::class, 'show'])->name('admin.logs.show');

    // 多语言文案编辑（规格书 §6.3.5：AdminLanguages）
    Route::get('/admin/languages', [AdminLanguages::class, 'index'])->name('admin.languages.index');
    Route::get('/admin/languages/{code}/edit', [AdminLanguages::class, 'edit'])->name('admin.languages.edit');
    Route::put('/admin/languages/{code}', [AdminLanguages::class, 'update'])->name('admin.languages.update');

        // Push 订阅者（规格书 §6.3.4：AdminPushSubscribers，插件 push-notifications）
    Route::get('/admin/push-subscribers', [AdminPushSubscribers::class, 'index'])->name('admin.push-subscribers.index');
        Route::delete('/admin/push-subscribers/{subscriberId}', [AdminPushSubscribers::class, 'destroy'])->name('admin.push-subscribers.destroy');

    // Push 通知 Campaign 管理（规格书 §6.3.4 / §14.5：AdminPushNotifications）
    Route::get('/admin/push-notifications', [AdminNotifications::class, 'pushIndex'])->name('admin.push-notifications.index');
    Route::get('/admin/push-notifications/create', [AdminNotifications::class, 'pushCreate'])->name('admin.push-notifications.create');
    Route::post('/admin/push-notifications', [AdminNotifications::class, 'pushStore'])->name('admin.push-notifications.store');
    Route::get('/admin/push-notifications/{campaign}/edit', [AdminNotifications::class, 'pushEdit'])->name('admin.push-notifications.edit');
    Route::put('/admin/push-notifications/{campaign}', [AdminNotifications::class, 'pushUpdate'])->name('admin.push-notifications.update');
    Route::delete('/admin/push-notifications/{campaign}', [AdminNotifications::class, 'pushDestroy'])->name('admin.push-notifications.destroy');

    // 多语言 CRUD（规格书 §6.3.5：AdminLanguages）
    Route::get('/admin/languages/create', [AdminLanguages::class, 'create'])->name('admin.languages.create');
    Route::post('/admin/languages', [AdminLanguages::class, 'store'])->name('admin.languages.store');

    // 信用票据管理（规格书 §6.3.3：AdminCreditNotes）
    Route::get('/admin/credit-notes', [AdminInvoice::class, 'creditNotesIndex'])->name('admin.credit-notes.index');

    // 管理后台 - 统计子页面（规格书 附B：AdminStatistics.database/growth/users/payments）
    Route::get('/admin/statistics/database', [AdminStatistics::class, 'database'])->name('admin.statistics.database');
    Route::get('/admin/statistics/local-files', [AdminStatistics::class, 'localFiles'])->name('admin.statistics.local-files');
    Route::get('/admin/statistics/growth', [AdminStatistics::class, 'growth'])->name('admin.statistics.growth');
    Route::get('/admin/statistics/users', [AdminStatistics::class, 'users'])->name('admin.statistics.users');
    Route::get('/admin/statistics/payments', [AdminStatistics::class, 'payments'])->name('admin.statistics.payments');

        // 管理后台 - 授权许可（规格书 §15.2）
    Route::get('/admin/license', [AdminLicense::class, 'index'])->name('admin.license.index');
    Route::post('/admin/license/upload', [AdminLicense::class, 'upload'])->name('admin.license.upload');
    Route::get('/admin/license/refresh', [AdminLicense::class, 'refresh'])->name('admin.license.refresh');
});

// ========================================
// Cron 定时任务端点（规格书 §13）
// ========================================
Route::get('/cron', [CronController::class, 'index'])->name('cron');
Route::get('/cron/{task}', [CronController::class, 'task'])->whereIn('task', ['email_reports', 'broadcasts', 'push_notifications'])->name('cron.task'); // 规格 §13.1 子任务

// 安装向导（规格 §15.3/§19：storage/installed.lock 存在即失效）
Route::get('/install', [InstallController::class, 'index'])->name('install');
Route::post('/install/database', [InstallController::class, 'database'])->name('install.database');
Route::get('/install/admin', [InstallController::class, 'showAdmin'])->name('install.admin');
Route::post('/install/admin', [InstallController::class, 'admin'])->name('install.admin.submit');

// ========================================
// 插件端点（规格书 §14）
// ========================================

// PWA 插件（规格书 §14.6）
Route::get('/pwa/manifest.json', function () {
    $settings = \App\Models\Setting::getGroup('pwa');
    return response()->json([
        'name' => $settings['name'] ?? config('app.name'),
        'short_name' => $settings['short_name'] ?? 'Monit',
        'description' => $settings['description'] ?? '',
        'start_url' => '/',
        'display' => 'standalone',
        'theme_color' => $settings['theme_color'] ?? '#4f46e5',
        'background_color' => $settings['background_color'] ?? '#ffffff',
        'icons' => $settings['icons'] ?? [],
    ]);
})->name('pwa.manifest');

// PWA Service Worker（规格书 §14.6）
Route::get('/pwa/sw.js', function () {
    $content = view('pwa.sw')->render();

    return response($content)
        ->header('Content-Type', 'application/javascript')
        ->header('Service-Worker-Allowed', '/');
})->name('pwa.sw');

// Push Notifications 前端订阅（规格书 §14.5）
Route::post('/push-notifications/subscribe', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'endpoint' => ['required', 'url'],
        'keys.auth' => ['required', 'string'],
        'keys.p256dh' => ['required', 'string'],
    ]);
        \App\Models\PushNotificationSubscriber::create([
        'user_id' => auth()->id(),
        'endpoint' => $validated['endpoint'],
        'keys_auth' => $validated['keys']['auth'],
        'keys_p256dh' => $validated['keys']['p256dh'],
        'subscriber_datetime' => now(),
    ]);
    return response()->json(['status' => 'subscribed']);
})->middleware('auth')->name('push-notifications.subscribe');

// Push Notifications 取消订阅（规格书 §14.5）
Route::post('/push-notifications/unsubscribe', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'endpoint' => ['required', 'url'],
    ]);

    \App\Models\PushNotificationSubscriber::where('user_id', auth()->id())
        ->where('endpoint', $validated['endpoint'])
        ->delete();

    return response()->json(['status' => 'unsubscribed']);
})->middleware('auth')->name('push-notifications.unsubscribe');

// Dynamic OG Images 插件端点（规格书 §14.7）
Route::get('/dynamic-og-images/{type}/{id}', function (string $type, int $id) {
    $imageService = app(\App\Services\DynamicOgImageService::class);
    return $imageService->generate($type, $id);
})->name('dynamic-og-images.generate');

// 404 兜底路由（规格书 §6.1：/not-found）
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
