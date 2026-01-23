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
        Schema::table('orders', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('orders', 'payment_status')) {
                // Payment Status
                $table->enum('payment_status', [
                    'pending',           // Order created, payment not initiated
                    'awaiting_review',   // Bank transfer proof uploaded, waiting for admin review
                    'processing',        // Payment initiated, waiting for confirmation
                    'paid',              // Payment successful
                    'failed',            // Payment failed
                    'cancelled',         // Payment cancelled by user
                    'expired',           // Payment link expired
                    'refunded',          // Fully refunded
                    'partially_refunded' // Partially refunded
                ])->default('pending')->after('status');

                // Index for faster queries
                $table->index('payment_status');
            }

            if (!Schema::hasColumn('orders', 'payment_transaction_id')) {
                // Link to latest payment transaction
                $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null')->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_transaction_id']);
            $table->dropIndex(['payment_status']);
            $table->dropColumn(['payment_status', 'payment_transaction_id']);
        });
    }
};
