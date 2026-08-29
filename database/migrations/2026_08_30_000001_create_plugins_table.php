<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 插件系统（规格书 §14）：插件注册表
 * 状态机：uninstalled(无行) → installed(0) → active(1)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->string('plugin_id', 64)->primary();
            $table->string('name', 128);
            $table->unsignedTinyInteger('is_installed')->default(0);
            $table->unsignedTinyInteger('is_active')->default(0);
            $table->json('settings')->nullable();
            $table->timestamp('datetime')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
