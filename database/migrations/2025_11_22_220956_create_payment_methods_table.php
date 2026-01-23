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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name_en')->nullable();
            $table->string('name_ar')->nullable();

            $table->string('image')->nullable();

            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            // نسبة الزيادة (percentage)
            $table->decimal('processing_fee_percentage', 8, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
