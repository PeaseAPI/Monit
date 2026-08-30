<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Image Optimizer 插件统计表（规格书 §14.9）
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('image_optimizer_stats')) {
            return;
        }

        Schema::create('image_optimizer_stats', function (Blueprint $table) {
            $table->id('stat_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('file_type', 16)->nullable();    // jpeg / png / gif
            $table->unsignedBigInteger('original_size')->default(0);
            $table->unsignedBigInteger('optimized_size')->default(0);
            $table->timestamp('datetime')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_optimizer_stats');
    }
};
