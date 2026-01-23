<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // Payment Gateway Type
            $table->string('gateway')->nullable()->after('status'); // cash, bank_transfer, tamara, tabby, amwal

            // Gateway Configuration (store credentials, settings, etc.)
            $table->json('gateway_config')->nullable()->after('gateway');

            // Test Mode Toggle
            $table->boolean('test_mode')->default(false)->after('gateway_config');

            // Index for faster queries
            $table->index('gateway');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropIndex(['gateway']);
            $table->dropColumn(['gateway', 'gateway_config', 'test_mode']);
        });
    }
};
