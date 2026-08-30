<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M1 Schema 对齐规格书 §3：
 * - domains.type（0=用户自定义域名 / 1=平台主域名，§3.1）
 * - payments.base_amount / taxes_amount（§3.1 金额拆分，供发票与信用票据）
 * - codes.redeemed（§3.1 已兑换计数，兑换时递增；与 redeemed_codes 记录双轨，便于列表直查）
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(0)->index()->after('host');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('base_amount', 12, 2)->nullable()->after('frequency');
            $table->decimal('taxes_amount', 12, 2)->default(0)->after('discount_amount');
        });

        Schema::table('codes', function (Blueprint $table) {
            $table->unsignedInteger('redeemed')->default(0)->after('max_redemptions');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['base_amount', 'taxes_amount']);
        });

        Schema::table('codes', function (Blueprint $table) {
            $table->dropColumn('redeemed');
        });
    }
};
