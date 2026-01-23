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
            // Shipping provider (default OTO)
            $table->string('shipping_provider')->default('OTO')->after('payment_transaction_id');
            
            // Tracking information
            $table->string('tracking_number')->nullable()->after('shipping_provider');
            $table->string('tracking_status')->nullable()->after('tracking_number');
            $table->string('tracking_url')->nullable()->after('tracking_status');
            
            // Provider-specific reference (may differ from tracking number)
            $table->string('shipping_reference')->nullable()->after('tracking_url');
            
            // Estimated time of arrival
            $table->string('shipping_eta')->nullable()->after('shipping_reference');
            
            // Last status update timestamp
            $table->timestamp('shipping_status_updated_at')->nullable()->after('shipping_eta');
            
            // Full provider payload storage
            $table->json('shipping_payload')->nullable()->after('shipping_status_updated_at');
            
            // Index for webhook lookups
            $table->index('tracking_number');
            $table->index('shipping_reference');
            $table->index(['shipping_provider', 'tracking_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['orders_tracking_number_index']);
            $table->dropIndex(['orders_shipping_reference_index']);
            $table->dropIndex(['orders_shipping_provider_tracking_status_index']);
            
            $table->dropColumn([
                'shipping_provider',
                'tracking_number',
                'tracking_status',
                'tracking_url',
                'shipping_reference',
                'shipping_eta',
                'shipping_status_updated_at',
                'shipping_payload',
            ]);
        });
    }
};



