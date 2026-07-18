<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drops the three legacy category payment flags.
 *
 * They used to make a department exclusive: is_bank_transfer showed only
 * bank transfer, is_madfu showed only Madfu, is_installment hid Madfu, and
 * an unflagged department hid bank transfer, Madfu and installments alike.
 *
 * Every department now offers every active payment method, with Madfu hidden
 * for the phones subtree via categories.excludes_madfu. Nothing reads these
 * three columns any more, so they are removed rather than left to confuse.
 *
 * Ships together with the application code that stops reading them.
 */
return new class extends Migration
{
    private const LEGACY_FLAGS = ['is_bank_transfer', 'is_installment', 'is_madfu'];

    public function up(): void
    {
        $present = array_values(array_filter(
            self::LEGACY_FLAGS,
            fn (string $column) => Schema::hasColumn('categories', $column)
        ));

        if (! $present) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) use ($present) {
            $table->dropColumn($present);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            foreach (self::LEGACY_FLAGS as $column) {
                if (! Schema::hasColumn('categories', $column)) {
                    $table->boolean($column)->default(false);
                }
            }
        });
    }
};
