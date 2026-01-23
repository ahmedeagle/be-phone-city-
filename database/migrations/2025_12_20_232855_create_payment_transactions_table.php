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
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            
            // Relations
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('restrict');
            
            // Payment Gateway Info
            $table->string('gateway'); // cash, bank_transfer, tamara, tabby, amwal
            $table->string('transaction_id')->nullable()->unique(); // External transaction ID from gateway
            
            // Amount & Currency
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            
            // Status
            $table->enum('status', [
                'pending',
                'processing',
                'success',
                'failed',
                'expired',
                'cancelled',
                'refunded',
                'partially_refunded'
            ])->default('pending');
            
            // Request & Response Data
            $table->json('request_payload')->nullable(); // Data sent to gateway
            $table->json('response_payload')->nullable(); // Response from gateway
            $table->text('error_message')->nullable(); // Error details if failed
            
            // Bank Transfer Specific Fields
            $table->string('payment_proof_path')->nullable(); // Path to uploaded payment proof
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null'); // Admin who reviewed
            $table->timestamp('reviewed_at')->nullable(); // When it was reviewed
            $table->text('review_notes')->nullable(); // Admin notes on approval/rejection
            
            // Expiration
            $table->timestamp('expires_at')->nullable(); // Payment session expiration
            
            $table->timestamps();
            
            // Indexes
            $table->index(['order_id', 'status']);
            $table->index('gateway');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
