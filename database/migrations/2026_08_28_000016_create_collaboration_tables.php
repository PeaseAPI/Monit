<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // teams
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('team_id');
            $table->unsignedInteger('user_id');
            $table->string('name', 64);
            $table->dateTime('datetime');
        });

        // team_members
        Schema::create('team_members', function (Blueprint $table) {
            $table->increments('team_member_id');
            $table->unsignedInteger('team_id');
            $table->string('user_email', 320);
            $table->unsignedInteger('user_id')->nullable();
            $table->boolean('is_owned')->default(false);
            $table->json('websites_ids')->nullable();
            $table->json('access')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->dateTime('last_activity')->nullable();
            $table->dateTime('datetime');

            $table->unique(['team_id', 'user_email']);
        });

        // internal_notifications
        Schema::create('internal_notifications', function (Blueprint $table) {
            $table->increments('internal_notification_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('from_user_id')->nullable();
            $table->string('for_type', 64);
            $table->unsignedInteger('for_id')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('datetime');
        });

        // codes (redeem)
        Schema::create('codes', function (Blueprint $table) {
            $table->increments('code_id');
            $table->string('name', 256);
            $table->string('code', 64)->unique();
            $table->enum('type', ['discount', 'plan'])->default('plan');
            $table->string('plan_id', 64)->nullable();
            $table->integer('days')->nullable();
            $table->decimal('discount', 5, 2)->nullable();
            $table->integer('max_redemptions')->default(0);
            $table->dateTime('date_start')->nullable();
            $table->dateTime('date_end')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('datetime');
        });

        // redeemed_codes
        Schema::create('redeemed_codes', function (Blueprint $table) {
            $table->increments('redeemed_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('code_id');
            $table->dateTime('datetime');
        });

        // affiliates_withdrawals
        Schema::create('affiliates_withdrawals', function (Blueprint $table) {
            $table->increments('affiliate_withdrawal_id');
            $table->unsignedInteger('user_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('note', 1024)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliates_withdrawals');
        Schema::dropIfExists('redeemed_codes');
        Schema::dropIfExists('codes');
        Schema::dropIfExists('internal_notifications');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
