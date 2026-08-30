<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M17 短信验证：users 表增加手机号字段
 * - phone：手机号（规范化后的纯数字，如 13800138000；支持短信登录/找回/绑定）
 * - phone_verified_at：手机号验证时间（短信验证码校验通过时写入）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->unique()->after('email');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'phone_verified_at']);
        });
    }
};
