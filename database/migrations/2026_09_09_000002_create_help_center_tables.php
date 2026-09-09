<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Monit: 帮助中心表（help_categories / help_articles）
 * 后台可维护分类与文章；前台 /help 仿帮助中心样式展示，无数据时回退内置静态内容
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 64);
            $table->string('url', 256);
            $table->string('icon', 32)->default('book');
            $table->integer('order')->default(0);
            $table->dateTime('datetime');
        });

        Schema::create('help_articles', function (Blueprint $table) {
            $table->increments('article_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('title', 256);
            $table->string('url', 256);
            $table->longText('content');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('views')->default(0);
            $table->integer('order')->default(0);
            $table->dateTime('datetime')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('help_categories');
    }
};
