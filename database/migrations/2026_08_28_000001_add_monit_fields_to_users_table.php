<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: 扩展 Laravel 默认 users 表
 * 依据规格书 §3.1 users 表（User.php 反推）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 0=普通用户, 1=Admin
            $table->unsignedTinyInteger('type')->default(0)->after('id')->index();
            // 账单信息 JSON
            $table->json('billing')->nullable()->after('password');
            // Bearer Token API 密钥
            $table->string('api_key', 64)->nullable()->unique()->after('billing');
            // 邮箱激活码 / 找回密码码
            $table->string('email_activation_code', 64)->nullable()->after('api_key');
            $table->string('lost_password_code', 64)->nullable()->after('email_activation_code');
            $table->boolean('is_newsletter_subscribed')->default(false)->after('lost_password_code');
            // 套餐：free / guest / custom / 数字ID
            $table->string('plan_id', 64)->default('free')->after('is_newsletter_subscribed');
            $table->timestamp('plan_expiration_date')->nullable()->after('plan_id');
            $table->json('plan_settings')->nullable()->after('plan_expiration_date');
            $table->boolean('plan_trial_done')->default(false)->after('plan_settings');
            $table->boolean('plan_expiry_reminder')->default(false)->after('plan_trial_done');
            $table->boolean('user_deletion_reminder')->default(false)->after('plan_expiry_reminder');
            // 推荐返佣
            $table->string('referral_key', 64)->nullable()->unique()->after('user_deletion_reminder');
            $table->foreignId('referred_by')->nullable()->constrained('users', 'user_id')->nullOnDelete()->after('referral_key');
            $table->boolean('referred_by_has_converted')->default(false)->after('referred_by');
            // 支付信息
            $table->string('payment_subscription_id', 256)->nullable()->after('referred_by_has_converted');
            $table->string('payment_processor', 64)->nullable()->after('payment_subscription_id');
            $table->decimal('payment_total_amount', 12, 2)->nullable()->after('payment_processor');
            $table->string('payment_currency', 3)->nullable()->after('payment_total_amount');
            // 偏好
            $table->string('language', 10)->default('zh_CN')->after('payment_currency');
            $table->string('timezone', 64)->default('Asia/Shanghai')->after('language');
            // 0=未激活, 1=正常
            $table->unsignedTinyInteger('status')->default(1)->after('timezone');
            $table->string('source', 64)->nullable()->after('status');
            $table->string('ip', 45)->nullable()->after('source');
            $table->decimal('latitude', 10, 7)->nullable()->after('ip');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('continent_code', 2)->nullable()->after('longitude');
            $table->string('country', 64)->nullable()->after('continent_code');
            $table->string('city_name', 64)->nullable()->after('country');
            $table->string('device_type', 16)->nullable()->after('city_name');
            $table->string('os_name', 64)->nullable()->after('device_type');
            $table->string('browser_name', 64)->nullable()->after('os_name');
            $table->timestamp('last_activity')->nullable()->after('browser_name');
            $table->unsignedInteger('total_logins')->default(0)->after('last_activity');
            $table->json('preferences')->nullable()->after('total_logins');
            $table->string('avatar', 256)->nullable()->after('preferences');
            $table->string('anti_phishing_code', 32)->nullable()->after('avatar');
            $table->string('twofa_token', 64)->nullable()->after('anti_phishing_code');
            $table->boolean('twofa_is_enabled')->default(false)->after('twofa_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'billing', 'api_key', 'email_activation_code', 'lost_password_code',
                'is_newsletter_subscribed', 'plan_id', 'plan_expiration_date', 'plan_settings',
                'plan_trial_done', 'plan_expiry_reminder', 'user_deletion_reminder', 'referral_key',
                'referred_by', 'referred_by_has_converted', 'payment_subscription_id',
                'payment_processor', 'payment_total_amount', 'payment_currency', 'language',
                'timezone', 'status', 'source', 'ip', 'latitude', 'longitude', 'continent_code',
                'country', 'city_name', 'device_type', 'os_name', 'browser_name', 'last_activity',
                'total_logins', 'preferences', 'avatar', 'anti_phishing_code', 'twofa_token',
                'twofa_is_enabled',
            ]);
        });
    }
};
