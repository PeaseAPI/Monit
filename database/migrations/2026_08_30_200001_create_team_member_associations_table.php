<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_associations', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->string('role', 32)->default('member');
            $table->dateTime('datetime');

            $table->unique(['user_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_associations');
    }
};
