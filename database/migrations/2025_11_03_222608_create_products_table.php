<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('main_image')->nullable();
            $table->text('description_en');
            $table->text('description_ar');
            $table->text('details_en');
            $table->text('details_ar');
            $table->text('about_en');
            $table->text('about_ar');
            $table->string('product_mark'); // علامة المنتج
            $table->string('capacity')->nullable(); // سعة المنتج
            $table->integer('points')->default(0)->nullable(); // نقاط المنتج
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('main_price', 10, 2);
            $table->integer('quantity')->default(0);
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'limited'])->default('in_stock');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
