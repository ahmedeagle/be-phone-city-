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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('website_title_en')->nullable();
            $table->string('website_title_ar')->nullable();
            $table->string('logo')->nullable();
            $table->decimal('free_shipping_threshold', 10, 2)->default(0.00);
            $table->decimal('tax_percentage', 5, 2)->default(0.00);
            $table->integer('points_days_expired')->default(365);
            $table->decimal('point_value', 10, 2)->default(1.00);
            $table->string('currency')->default('SAR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
