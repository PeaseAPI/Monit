<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: plans 套餐表
 * 依据规格书 §3.1 plans 表（Plan.php 反推）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            // 套餐ID：'free'/'guest'/'custom'/数字自增ID
            $table->string('plan_id', 64)->primary();
            $table->string('name', 256);
            $table->text('description')->nullable();
            // 各货币各周期定价 {USD: {monthly:9.99, annual:99.99, lifetime:199.99}}
            $table->json('prices')->nullable();
            // 功能设置（websites_limit / sessions_events_limit 等）
            $table->json('settings')->nullable();
            // 额外设置（支付/试用等）
            $table->json('additional_settings')->nullable();
            $table->json('translations')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->unsignedInteger('trial_days')->default(0);
            $table->json('taxes_ids')->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
