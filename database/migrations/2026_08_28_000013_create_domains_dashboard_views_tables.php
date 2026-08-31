<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // domains
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('domain_id');
            $table->foreignId('user_id');
            $table->string('scheme', 8)->default('https');
            $table->string('host', 256);
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('datetime');

            $table->unique(['user_id', 'host']);
        });

        // dashboard_views
        Schema::create('dashboard_views', function (Blueprint $table) {
            $table->increments('dashboard_view_id');
            $table->foreignId('website_id')->nullable();
            $table->foreignId('user_id');
            $table->string('name', 128);
            $table->json('settings');
            $table->unsignedInteger('order')->default(0);
            $table->dateTime('datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_views');
        Schema::dropIfExists('domains');
    }
};
