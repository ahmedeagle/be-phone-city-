<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_installment')->default(false)->after('is_bank_transfer');
        });

        // Update the existing "installment" category name and flag it
        DB::table('categories')->where('slug', 'installment')->update([
            'name_ar' => 'تقسيط اموال وتابي',
            'name_en' => 'Aman & Tabby Installments',
            'is_installment' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_installment');
        });
    }
};
