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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('auto_confirm_electronic_payments')->default(true)
                ->after('bank_instructions')
                ->comment('Auto-confirm orders paid via electronic gateways (Tabby, Tamara, Amwal, Moyasar)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('auto_confirm_electronic_payments');
        });
    }
};
