<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: websites_goals 目标表 + goals_conversions 目标转化表
 * 依据规格书 §3.2 websites_goals / goals_conversions 表
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('websites_goals', function (Blueprint $table) {
            $table->id('goal_id');
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->string('key', 64);
            // pageview / scroll / custom
            $table->string('type', 32)->default('custom');
            // 支持 * 通配符
            $table->string('path', 2048)->nullable();
            $table->unsignedTinyInteger('scroll_percentage')->nullable();
            $table->string('name', 256);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['website_id', 'key']);
            $table->index(['website_id', 'is_enabled']);
        });

        Schema::create('goals_conversions', function (Blueprint $table) {
            $table->id('conversion_id');
            $table->foreignId('goal_id')->constrained('websites_goals', 'goal_id')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('sessions_events', 'event_id')->cascadeOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('visitors_sessions', 'session_id')->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->date('expiration_date')->nullable()->index();
            $table->timestamps();

            $table->index(['website_id', 'goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals_conversions');
        Schema::dropIfExists('websites_goals');
    }
};
