<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure every payment method whose gateway is 'madfu' is properly flagged.
        DB::table('payment_methods')
            ->where('gateway', 'madfu')
            ->update(['is_madfu' => true, 'is_installment' => true, 'is_bank_transfer' => false]);

        // Also catch any manually-created record named "مدفوع" without a gateway.
        DB::table('payment_methods')
            ->where('name_ar', 'مدفوع')
            ->whereNull('gateway')
            ->update(['is_madfu' => true, 'is_installment' => true, 'is_bank_transfer' => false]);
    }

    public function down(): void
    {
        // Non-destructive – flags set here are correct; no rollback needed.
    }
};
