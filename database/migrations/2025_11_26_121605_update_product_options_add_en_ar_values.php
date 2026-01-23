<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {

            // Add en & ar columns
            $table->string('value_en')->nullable()->after('type');
            $table->string('value_ar')->nullable()->after('value_en');

            // Remove old value column if exists
            if (Schema::hasColumn('product_options', 'value')) {
                $table->dropColumn('value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {

            // Rollback: re-add old value
            $table->string('value')->nullable();

            // Drop new columns
            if (Schema::hasColumn('product_options', 'value_en')) {
                $table->dropColumn('value_en');
            }
            if (Schema::hasColumn('product_options', 'value_ar')) {
                $table->dropColumn('value_ar');
            }
        });
    }
};
