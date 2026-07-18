<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Opens every payment method to every department.
 *
 * Previously a category could carry is_bank_transfer / is_installment / is_madfu,
 * and each of those acted as an *exclusive* restriction: a Madfu category showed
 * Madfu and nothing else, an installment category hid Madfu, and every ordinary
 * category hid bank transfer, Madfu and (usually) installments too.
 *
 * The new rule is the inverse: every active payment method shows everywhere,
 * except Madfu, which is hidden for the smart-devices (phones) subtree.
 */
return new class extends Migration
{
    /** Slug of the department whose subtree must not offer Madfu. */
    private const MADFU_EXCLUDED_SLUG = 'electronics'; // الأجهزة الذكية

    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('excludes_madfu')
                ->default(false)
                ->after('is_madfu');
        });

        // Flag only the root of the subtree — descendants are resolved in code
        // (Category::madfuExcludedIds), so newly added phone brands inherit it.
        DB::table('categories')
            ->where('slug', self::MADFU_EXCLUDED_SLUG)
            ->update(['excludes_madfu' => true]);

        // Retire the old exclusive restrictions so every other department opens up.
        // The code paths behind these flags still exist, so any of them can be
        // switched back on from the admin panel if a department needs it again.
        DB::table('categories')->update([
            'is_bank_transfer' => false,
            'is_installment' => false,
            'is_madfu' => false,
        ]);
    }

    public function down(): void
    {
        // Restore the three departments that carried a restriction before this change.
        DB::table('categories')->where('slug', 'show-you')->update(['is_bank_transfer' => true]);
        DB::table('categories')->where('slug', 'installment')->update(['is_installment' => true]);
        DB::table('categories')->where('slug', 'madfu-installments')->update(['is_madfu' => true]);

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('excludes_madfu');
        });
    }
};
