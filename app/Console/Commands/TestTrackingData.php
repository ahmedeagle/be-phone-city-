<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class TestTrackingData extends Command
{
    protected $signature = 'order:test-tracking {order_number}';
    protected $description = 'Add test tracking data to an order for testing the tracking card';

    public function handle(): int
    {
        $order = Order::where('order_number', $this->argument('order_number'))->first();

        if (!$order) {
            $this->error('Order not found!');
            return self::FAILURE;
        }

        $order->update([
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => 'TEST-' . strtoupper(substr(md5(now()), 0, 8)),
            'tracking_url' => null, // Real URL comes from OTO API (trackingUrl / printAWBURL)
            'tracking_status' => 'in_transit',
            'shipping_eta' => now()->addDays(2)->toDateString(),
            'shipping_status_updated_at' => now(),
        ]);

        $this->info("✅ Test tracking data added to order #{$order->order_number}");
        $this->table(
            ['Field', 'Value'],
            [
                ['Status', $order->status],
                ['Tracking Number', $order->tracking_number],
                ['Tracking URL', $order->tracking_url],
                ['Tracking Status', $order->tracking_status],
                ['Shipping ETA', $order->shipping_eta],
            ]
        );

        return self::SUCCESS;
    }
}
