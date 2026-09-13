<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 社交账号绑定表（账户页「社交登录」标签，用户反馈 #M 轮任务）
 * - user_id + provider 唯一：同一 Monit 账号每个平台仅绑定一个身份
 * - provider + provider_user_id 唯一：同一第三方身份全局仅归属一个账号（防跨账号重复承接）
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('social_accounts')) {
            Schema::create('social_accounts', function (Blueprint $table) {
                $table->increments('social_account_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->string('provider', 32);
                $table->string('provider_user_id', 128);
                $table->string('nickname', 128)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('avatar', 512)->nullable();
                $table->dateTime('datetime');
                $table->timestamp('created_at')->nullable();

                $table->unique(['user_id', 'provider']);
                $table->unique(['provider', 'provider_user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
