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
            $table->boolean('show_new_arrivals_section')->default(true)->after('point_value');
            $table->boolean('show_featured_section')->default(true)->after('show_new_arrivals_section');
            $table->integer('new_arrivals_count')->default(10)->after('show_featured_section');
            $table->integer('featured_count')->default(10)->after('new_arrivals_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'show_new_arrivals_section',
                'show_featured_section',
                'new_arrivals_count',
                'featured_count',
            ]);
        });
    }
};
