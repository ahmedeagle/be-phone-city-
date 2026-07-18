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

        // NOTE: the legacy is_bank_transfer / is_installment / is_madfu category
        // flags are deliberately NOT cleared here. Clearing them only makes sense
        // once the new resolution code is live: under the current code an
        // unflagged category hides bank transfer and Madfu entirely, so doing it
        // now would remove payment options from the storefront. That data change
        // ships alongside the application code instead.
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('excludes_madfu');
        });
    }
};
