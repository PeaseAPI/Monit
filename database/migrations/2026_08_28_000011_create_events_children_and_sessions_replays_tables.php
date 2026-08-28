<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: events_children 事件子项表 + sessions_replays 会话回放表 [ADV 模式]
 * 依据规格书 §3.2 events_children / sessions_replays 表
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events_children', function (Blueprint $table) {
            $table->id('event_child_id');
            $table->foreignId('event_id')->constrained('sessions_events', 'event_id')->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('visitors_sessions', 'session_id')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            // click / scroll / form / resize
            $table->string('type', 32)->index();
            $table->json('data')->nullable();
            $table->unsignedInteger('count')->default(1);
            $table->timestamp('date')->nullable()->index();
            $table->date('expiration_date')->nullable()->index();

            $table->index(['website_id', 'type', 'date']);
        });

        Schema::create('sessions_replays', function (Blueprint $table) {
            $table->id('replay_id');
            $table->foreignId('session_id')->constrained('visitors_sessions', 'session_id')->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained('websites_visitors', 'visitor_id')->cascadeOnDelete();
            $table->foreignId('website_id')->constrained('websites', 'website_id')->cascadeOnDelete();
            $table->boolean('is_offloaded')->default(false);
            $table->timestamp('datetime')->nullable()->index();

            $table->index(['website_id', 'datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions_replays');
        Schema::dropIfExists('events_children');
    }
};
