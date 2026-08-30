<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Push Notifications 插件数据表（规格书 §14.5）
 * push_notifications_subscribers + push_notifications_campaigns
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('push_notifications_subscribers')) {
            return;
        }

        Schema::create('push_notifications_subscribers', function (Blueprint $table) {
            $table->id('subscriber_id');
            $table->unsignedBigInteger('website_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('endpoint')->unique();
            $table->text('keys_p256dh');
            $table->text('keys_auth');
            $table->string('ip', 64)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('city', 128)->nullable();
            $table->timestamp('subscriber_datetime')->nullable()->index();
        });

        Schema::create('push_notifications_campaigns', function (Blueprint $table) {
            $table->id('campaign_id');
            $table->unsignedBigInteger('website_id')->index();
            $table->string('name', 256);
            $table->string('title', 256);
            $table->text('description')->nullable();
            $table->text('url')->nullable();
            $table->text('icon')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_sent')->default(false)->index();
            $table->timestamp('sent_datetime')->nullable();
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->timestamp('datetime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notifications_subscribers');
        Schema::dropIfExists('push_notifications_campaigns');
    }
};
