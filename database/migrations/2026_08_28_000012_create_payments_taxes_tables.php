<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payments
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->foreignId('user_id');
            $table->string('name', 256);
            $table->string('email', 256);
            $table->string('external_id', 256)->nullable();
            $table->string('payment_processor', 64);
            $table->string('type', 64);
            $table->string('frequency', 64);
            $table->json('billing')->nullable();
            $table->tinyInteger('status');
            $table->string('code_id', 64)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3);
            $table->dateTime('datetime');
            $table->dateTime('last_datetime')->nullable();
        });

        // taxes
        Schema::create('taxes', function (Blueprint $table) {
            $table->increments('tax_id');
            $table->string('name', 256);
            $table->string('description', 1024)->nullable();
            $table->decimal('value', 10, 4);
            $table->enum('value_type', ['percentage', 'fixed'])->default('percentage');
            $table->enum('type', ['inclusive', 'exclusive'])->default('exclusive');
            $table->enum('billing_type', ['personal', 'business'])->default('personal');
            $table->json('countries')->nullable();
            $table->dateTime('datetime');
        });

        // payments_audit
        Schema::create('payments_audit', function (Blueprint $table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->unsignedInteger('payment_id');
            $table->string('type', 64);
            $table->string('ip', 45);
            $table->dateTime('datetime');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments_audit');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('payments');
    }
};
