<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckDelayedShipments extends Command
{
    protected $signature = 'oto:check-delayed';

    protected $description = 'Check for shipments past their expected delivery date and notify admins';

    public function handle(): int
    {
        $delayedOrders = Order::query()
            ->where('delivery_method', Order::DELIVERY_HOME)
            ->whereNotNull('tracking_number')
            ->whereNotNull('shipping_eta')
            ->where('shipping_eta', '<', now())
            ->whereNotIn('status', [
                Order::STATUS_DELIVERED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ])
            ->get();

        if ($delayedOrders->isEmpty()) {
            $this->info('No delayed shipments found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$delayedOrders->count()} delayed shipments.");

        foreach ($delayedOrders as $order) {
            $this->line("  - Order #{$order->order_number} (ETA: {$order->shipping_eta}, Status: {$order->tracking_status})");
        }

        try {
            app(NotificationService::class)->notifyDelayedShipments($delayedOrders->toArray());
            $this->info('Admin notification sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send delayed shipments notification', ['error' => $e->getMessage()]);
            $this->error('Failed to send notification: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
