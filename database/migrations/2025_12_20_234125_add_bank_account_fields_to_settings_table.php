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
            // Bank Account Details for Bank Transfer Payment Method
            $table->string('bank_name')->nullable()->after('point_value');
            $table->string('account_holder')->nullable()->after('bank_name');
            $table->string('account_number')->nullable()->after('account_holder');
            $table->string('iban')->nullable()->after('account_number');
            $table->string('swift_code')->nullable()->after('iban');
            $table->string('branch')->nullable()->after('swift_code');
            $table->text('bank_instructions')->nullable()->after('branch');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_name',
                'account_holder',
                'account_number',
                'iban',
                'swift_code',
                'branch',
                'bank_instructions',
            ]);
        });
    }
};
