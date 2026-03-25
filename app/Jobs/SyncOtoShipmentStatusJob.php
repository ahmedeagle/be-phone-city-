<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Shipping\OtoShippingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to sync shipment status from OTO API for a single order
 */
class SyncOtoShipmentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public int $backoff = 60;

    /**
     * The order to sync
     */
    protected Order $order;

    /**
     * Create a new job instance
     */
    public function __construct(Order $order)
    {
        $this->order = $order;

        // Use default queue (matches the queue:work cron on server)
        $this->onQueue('default');
    }

    /**
     * Execute the job
     */
    public function handle(OtoShippingService $shippingService): void
    {
        // Reload order to ensure fresh data
        $this->order->refresh();

        // Skip if order doesn't have tracking info OR OTO order ID
        if (empty($this->order->tracking_number) && empty($this->order->oto_order_id)) {
            Log::warning('OTO sync skipped: No tracking number and no OTO order ID', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);
            return;
        }

        // Skip if order is already delivered or completed
        if (in_array($this->order->status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED])) {
            Log::info('OTO sync skipped: Order already in final status', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'status' => $this->order->status,
            ]);
            return;
        }

        try {
            Log::info('Syncing OTO shipment status', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'tracking_number' => $this->order->tracking_number,
            ]);

            $shippingService->syncShipmentStatus($this->order);

            Log::info('OTO shipment status synced successfully', [
                'order_id' => $this->order->id,
                'tracking_number' => $this->order->tracking_number,
                'tracking_status' => $this->order->tracking_status,
                'order_status' => $this->order->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync OTO shipment status', [
                'order_id' => $this->order->id,
                'tracking_number' => $this->order->tracking_number,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('OTO sync job failed permanently', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'tracking_number' => $this->order->tracking_number,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'oto-sync',
            'order:' . $this->order->id,
            'tracking:' . $this->order->tracking_number,
        ];
    }
}
