<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SEO 关键词排名跟踪 + 反链分析（SEO模块融合方案 §4 扩展）
 * - seo_keywords：跟踪的关键词（引擎/设备/区域/间隔），last/best 聚合列
 * - seo_keyword_ranks：排名快照（趋势数据源，position 为 null 表示掉出结果页）
 * - seo_backlinks：反链台账（手动/API 发现 + 定期重验活性）
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo_keywords')) {
            Schema::create('seo_keywords', function (Blueprint $table) {
                $table->increments('seo_keyword_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('website_id')->nullable()->index();
                $table->string('keyword', 256);
                $table->string('search_engine', 16)->default('google');
                $table->string('device', 8)->default('desktop');
                $table->string('locale', 16)->default('zh-CN');
                $table->string('target_url', 2048)->nullable();
                $table->string('check_interval', 16)->default('weekly');
                $table->boolean('is_enabled')->default(true)->index();
                $table->unsignedSmallInteger('last_position')->nullable();
                $table->unsignedSmallInteger('previous_position')->nullable();
                $table->unsignedSmallInteger('best_position')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'keyword', 'search_engine', 'device', 'locale'], 'seo_keywords_unique');
                $table->index(['is_enabled', 'check_interval']);
            });
        }

        if (! Schema::hasTable('seo_keyword_ranks')) {
            Schema::create('seo_keyword_ranks', function (Blueprint $table) {
                $table->increments('seo_keyword_rank_id');
                $table->unsignedInteger('seo_keyword_id')->index();
                $table->unsignedSmallInteger('position')->nullable();
                $table->string('url_found', 2048)->nullable();
                $table->string('source', 16)->default('auto');
                $table->timestamp('checked_at')->index();
                $table->timestamp('created_at')->nullable();

                $table->index(['seo_keyword_id', 'checked_at']);
            });
        }

        if (! Schema::hasTable('seo_backlinks')) {
            Schema::create('seo_backlinks', function (Blueprint $table) {
                $table->increments('seo_backlink_id');
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedInteger('website_id')->nullable()->index();
                $table->string('source_url', 2048);
                $table->string('source_host', 256)->index();
                $table->string('target_url', 2048)->nullable();
                $table->string('url_hash', 32)->comment('md5(source_url|target_url) 去重键');
                $table->string('anchor_text', 512)->nullable();
                $table->string('rel', 16)->default('unknown');
                $table->string('status', 16)->default('pending')->index();
                $table->unsignedSmallInteger('dr')->nullable();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('first_seen_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'url_hash'], 'seo_backlinks_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_keyword_ranks');
        Schema::dropIfExists('seo_keywords');
        Schema::dropIfExists('seo_backlinks');
    }
};
