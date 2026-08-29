<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // blog_posts
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 256);
            $table->string('url', 256);
            $table->unsignedInteger('category_id')->nullable();
            $table->longText('content');
            $table->text('description')->nullable();
            $table->string('image', 256)->nullable();
            $table->enum('type', ['blog', 'draft'])->default('draft');
            $table->boolean('is_published')->default(false);
            $table->dateTime('datetime');
        });

        // blog_posts_categories
        Schema::create('blog_posts_categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 64);
            $table->string('url', 256);
            $table->integer('order')->default(0);
            $table->dateTime('datetime');
        });

        // pages
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('page_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 256);
            $table->string('url', 256);
            $table->longText('content');
            $table->text('description')->nullable();
            $table->enum('type', ['page', 'draft'])->default('draft');
            $table->enum('position', ['header', 'footer', 'none'])->default('none');
            $table->integer('order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->dateTime('datetime');
        });

        // pages_categories
        Schema::create('pages_categories', function (Blueprint $table) {
            $table->increments('page_category_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 64);
            $table->string('url', 256);
            $table->integer('order')->default(0);
            $table->dateTime('datetime');
        });

                // broadcasts
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->increments('broadcast_id');
            $table->unsignedInteger('user_id');
            $table->string('title', 256)->nullable();
            $table->text('content')->nullable();
            $table->enum('type', ['email', 'push'])->default('email');
            $table->enum('status', ['draft', 'pending', 'processing', 'sent'])->default('draft');
            $table->string('target', 64)->default('all');
            $table->string('target_plan_id', 64)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedInteger('total_emails')->default(0);
            $table->unsignedInteger('total_sent')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->dateTime('sent_datetime')->nullable();
            $table->dateTime('datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
        Schema::dropIfExists('pages_categories');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('blog_posts_categories');
        Schema::dropIfExists('blog_posts');
    }
};
