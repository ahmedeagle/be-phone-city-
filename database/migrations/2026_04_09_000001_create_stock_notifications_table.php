<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_option_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('email');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'product_option_id', 'notified']);
            $table->unique(['product_id', 'product_option_id', 'email'], 'stock_notif_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notifications');
    }
};
