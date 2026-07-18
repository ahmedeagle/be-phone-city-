<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a per-payment-method discount, used to reward bank transfer (6%),
 * which costs the shop no gateway fees.
 *
 * The percentage is editable per method in the admin panel; the resulting
 * amount is frozen onto the order in `payment_discount` at checkout.
 */
return new class extends Migration
{
    private const BANK_TRANSFER_DISCOUNT = 6.00;

    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)
                ->default(0)
                ->after('processing_fee_percentage');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('payment_discount', 10, 2)
                ->default(0)
                ->after('points_discount');
        });

        DB::table('payment_methods')
            ->where('gateway', 'bank_transfer')
            ->update(['discount_percentage' => self::BANK_TRANSFER_DISCOUNT]);
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('discount_percentage');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_discount');
        });
    }
};
