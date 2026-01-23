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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->string('order_number')->unique();
            $table->text('notes')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_method_id')->constrained()->onDelete('restrict');
            $table->enum('delivery_method', ['home_delivery', 'store_pickup'])->default('home_delivery');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->foreignId('discount_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('payment_method_amount', 10, 2)->default(0);
            $table->decimal('points_discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('status')->default('Delivery is in progress');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
