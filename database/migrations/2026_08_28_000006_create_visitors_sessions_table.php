<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: visitors_sessions 会话表 [ADV 模式]
 * 依据规格书 §3.2 visitors_sessions 表
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors_sessions', function (Blueprint $table) {
            $table->id('session_id');
            // UUID 二进制（16 字节）
            $table->binary('session_uuid_binary', 16);
            $table->foreignId('visitor_id')->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->timestamp('date')->nullable();
            $table->unsignedBigInteger('total_events')->default(0);

            $table->unique(['website_id', 'session_uuid_binary', 'visitor_id']);
            $table->index(['website_id', 'date']);
            $table->index(['visitor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors_sessions');
    }
};
