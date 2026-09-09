<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: 工单系统表（tickets / ticket_replies）
 * - 登录用户与游客（email-only）均可提交工单
 * - 管理员后台回复 / 邮件入站回复统一走 ticket_replies
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->increments('ticket_id');
            $table->unsignedInteger('user_id')->nullable()->index();
            // 游客工单的回复邮箱（登录用户提交时同步快照一份，用户改邮箱后历史工单仍可达）
            $table->string('email', 256);
            $table->string('subject', 256);
            $table->string('category', 32)->default('general')->index();
            $table->string('priority', 16)->default('normal');
            // 0=open 待处理 1=answered 已回复（等待用户）2=closed 已关闭
            $table->tinyInteger('status')->default(0)->index();
            $table->dateTime('last_reply_at')->nullable();
            $table->dateTime('datetime')->index();
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->increments('reply_id');
            $table->unsignedInteger('ticket_id')->index();
            $table->unsignedInteger('user_id')->nullable();
            $table->boolean('is_staff')->default(false);
            $table->text('message');
            // web 后台表单 / email 邮件入站
            $table->string('via', 16)->default('web');
            $table->dateTime('datetime')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
    }
};
