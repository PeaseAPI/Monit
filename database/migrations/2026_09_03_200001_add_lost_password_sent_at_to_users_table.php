<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 密码重置码签发时间（TTL 消费方：ForgotPasswordController）
 * 之前 lost_password_code 永久有效——泄露的重置链接（邮件转发/日志/收件箱失窃）
 * 在被使用或再次申请前一直可用。现在 60 分钟窗口。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('lost_password_sent_at')->nullable()->after('lost_password_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('lost_password_sent_at');
        });
    }
};
