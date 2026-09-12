<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M30：地理维度升级到省级——websites_visitors / lightweight_events 增加省/州（region_name）列。
 * 数据源：GeoIp::lookup 新增 region_name（mmdb city 库 subdivisions.0，如「广东省」）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('websites_visitors', function (Blueprint $table): void {
            $table->string('region_name', 128)->nullable()->after('city_name');
        });

        Schema::table('lightweight_events', function (Blueprint $table): void {
            $table->string('region_name', 128)->nullable()->after('city_name');
        });
    }

    public function down(): void
    {
        Schema::table('websites_visitors', function (Blueprint $table): void {
            $table->dropColumn('region_name');
        });

        Schema::table('lightweight_events', function (Blueprint $table): void {
            $table->dropColumn('region_name');
        });
    }
};
