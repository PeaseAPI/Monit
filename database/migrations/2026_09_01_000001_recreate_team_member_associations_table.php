<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 修正 team_member_associations 表结构（规格书 §6.2.4：teams-associations-ajax）
 *
 * 原迁移（2026_08_30_200001）误按成员表模板建表（user_id/role），
 * 与 TeamMemberAssociation 模型及 TeamController::associationsAjax
 * 期望的结构（team_member_id/website_id/access）不符，导致该接口 SQLSTATE 1054 500。
 * 表当前无数据写入路径，直接重建为正确结构。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('team_member_associations');

        Schema::create('team_member_associations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('team_member_id');
            $table->foreignId('website_id');
            $table->json('access')->nullable();
            $table->dateTime('datetime');

            $table->unique(['team_member_id', 'website_id']);

            $table->foreign('team_member_id')
                ->references('team_member_id')
                ->on('team_members')
                ->cascadeOnDelete();
            $table->foreign('website_id')
                ->references('website_id')
                ->on('websites')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_associations');
    }
};
