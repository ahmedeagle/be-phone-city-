<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Shipping\OtoShippingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestOtoShipping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oto:test-shipping {order_id : The order ID to test shipping for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OTO shipping creation for a specific order';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        
        $order = Order::with(['location.city', 'items.product', 'items.productOption', 'user'])
            ->find($orderId);

        if (!$order) {
            $this->error("Order #{$orderId} not found!");
            return 1;
        }

        $this->info("Testing OTO shipping for Order #{$order->order_number} (ID: {$order->id})");
        $this->newLine();

        // Display order information
        $this->table(
            ['Field', 'Value'],
            [
                ['Order Number', $order->order_number],
                ['Status', $order->status],
                ['Payment Status', $order->payment_status],
                ['Delivery Method', $order->delivery_method],
                ['Has Location', $order->location ? 'Yes' : 'No'],
                ['Location ID', $order->location_id ?? 'N/A'],
                ['Has Active Shipment', $order->hasActiveShipment() ? 'Yes' : 'No'],
                ['OTO Order ID', $order->oto_order_id ?? 'N/A'],
                ['Tracking Number', $order->tracking_number ?? 'N/A'],
            ]
        );

        $this->newLine();

        // Check eligibility
        if ($order->payment_status !== Order::PAYMENT_STATUS_PAID) {
            $this->warn("⚠️  Order payment status is '{$order->payment_status}', not 'paid'");
            $this->warn("   Automatic shipping only triggers when payment status is 'paid'");
            $this->newLine();
        }

        if ($order->delivery_method !== Order::DELIVERY_HOME) {
            $this->warn("⚠️  Delivery method is '{$order->delivery_method}', not 'home_delivery'");
            $this->newLine();
        }

        if (!$order->location) {
            $this->error("❌ Order has no location!");
            return 1;
        }

        if ($order->hasActiveShipment()) {
            $this->warn("⚠️  Order already has an active shipment!");
            $this->warn("   Tracking: {$order->tracking_number}");
            $this->newLine();
        }

        if (!empty($order->oto_order_id)) {
            $this->info("ℹ️  Order already has OTO Order ID: {$order->oto_order_id}");
            $this->newLine();
        }

        // Ask for confirmation
        if (!$this->confirm('Do you want to proceed with creating OTO shipping?', true)) {
            $this->info('Cancelled.');
            return 0;
        }

        try {
            $this->info('Creating OTO order and shipment...');
            $this->newLine();

            $shippingService = app(OtoShippingService::class);
            
            // Update order status to processing if needed
            if (in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CONFIRMED])) {
                $this->info("Updating order status from '{$order->status}' to 'processing'...");
                $order->update(['status' => Order::STATUS_PROCESSING]);
                $order->refresh();
            }

            $shipmentDto = $shippingService->createOrderAndShipment($order);

            $order->refresh();

            $this->newLine();
            $this->info('✅ OTO shipping created successfully!');
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['OTO Order ID', $order->oto_order_id ?? 'N/A'],
                    ['Tracking Number', $order->tracking_number ?? 'Pending'],
                    ['Shipping Reference', $order->shipping_reference ?? 'N/A'],
                    ['Tracking URL', $order->tracking_url ?? 'N/A'],
                    ['Tracking Status', $order->tracking_status ?? 'N/A'],
                    ['Order Status', $order->status],
                ]
            );

            if ($order->tracking_url) {
                $this->newLine();
                $this->info("Tracking URL: {$order->tracking_url}");
            }

            Log::info('Test OTO shipping command completed successfully', [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
            ]);

            return 0;

        } catch (\App\Services\Shipping\Oto\Exceptions\OtoValidationException $e) {
            $this->error("❌ Validation Error: {$e->getMessage()}");
            Log::error('Test OTO shipping validation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return 1;
        } catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
            $this->error("❌ OTO API Error: {$e->getMessage()}");
            Log::error('Test OTO shipping API failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->error("File: {$e->getFile()}:{$e->getLine()}");
            Log::error('Test OTO shipping failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}
