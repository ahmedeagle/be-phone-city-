<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the new product_options table
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('type');
            $table->string('value');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->timestamps();
        });

        // 2. Drop the old product_colors table
        Schema::dropIfExists('product_colors');
    }


    public function down(): void
    {
        // rollback: recreate old product_colors table
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->string('sku')->nullable();
            $table->timestamps();
        });

        // drop new table
        Schema::dropIfExists('product_options');
    }
};
