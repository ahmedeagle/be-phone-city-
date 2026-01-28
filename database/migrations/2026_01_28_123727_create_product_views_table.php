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
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->timestamp('viewed_at');
            $table->boolean('offer_sent')->default(false);
            $table->timestamp('offer_sent_at')->nullable();
            $table->boolean('purchased')->default(false);
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'product_id']);
            $table->index(['viewed_at']);
            $table->index(['offer_sent', 'purchased']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_views');
    }
};
