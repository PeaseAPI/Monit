<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 安全/业务修复迁移：payments.plan_id
 *
 * 修复三个缺陷（此前 payments 表无 plan_id 列，但代码多处假设其存在）：
 * 1. 支付成功后 activatePlan() 按 user->plan_id 激活的是「用户当前套餐」而非「本次购买套餐」，
 *    用户在等待支付期间切换套餐会导致买 A 激活 B；
 * 2. /account-payments 列表 with('plan') 因 Payment::plan() 关系缺列支撑而 500；
 * 3. AdminPayments::store 提交的 plan_id 被 fillable 静默丢弃。
 *
 * 类型对齐 plans.plan_id（string），nullable 以兼容历史订单（回填 user->plan_id）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('plan_id', 64)->nullable()->after('external_id')->index();
        });

        // 历史订单回填：无法还原购买快照，回填下单用户的当前套餐（与旧行为一致）
        DB::statement(
            'UPDATE payments SET plan_id = (SELECT plan_id FROM users WHERE users.user_id = payments.user_id) WHERE plan_id IS NULL'
        );
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
