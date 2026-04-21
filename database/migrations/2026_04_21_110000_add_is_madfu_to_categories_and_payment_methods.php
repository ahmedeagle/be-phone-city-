<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_madfu')->default(false)->after('is_installment');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_madfu')->default(false)->after('is_bank_transfer');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_madfu');
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('is_madfu');
        });
    }
};
