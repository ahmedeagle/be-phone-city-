<?php

namespace App\Console\Commands;

use App\Jobs\SyncOtoShipmentStatusJob;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Console command to sync all active OTO shipments
 */
class SyncOtoShipmentsCommand extends Command
{
    /**
     * The name and signature of the console command
     */
    protected $signature = 'oto:sync-shipments 
                            {--limit=100 : Maximum number of orders to sync}
                            {--force : Force sync even for completed orders}';

    /**
     * The console command description
     */
    protected $description = 'Sync shipment status from OTO API for all active shipments';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $this->info('Starting OTO shipments sync...');

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Build query for orders with active shipments
        $query = Order::query()
            ->where('shipping_provider', 'OTO')
            ->whereNotNull('tracking_number')
            ->orderBy('shipping_status_updated_at', 'asc')
            ->orderBy('updated_at', 'asc');

        // Unless forced, only sync orders that are in transit
        if (!$force) {
            $query->whereIn('status', [
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_IN_PROGRESS,
            ]);
        }

        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found to sync.');
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} orders to sync.");

        $progressBar = $this->output->createProgressBar($orders->count());
        $progressBar->start();

        $dispatched = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            try {
                // Dispatch job to queue
                SyncOtoShipmentStatusJob::dispatch($order);
                $dispatched++;
            } catch (\Exception $e) {
                $this->error("\nFailed to dispatch sync job for Order #{$order->order_number}: {$e->getMessage()}");
                $skipped++;
                
                Log::error('Failed to dispatch OTO sync job', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Sync jobs dispatched: {$dispatched}");
        
        if ($skipped > 0) {
            $this->warn("Skipped: {$skipped}");
        }

        Log::info('OTO shipments sync command completed', [
            'total_orders' => $orders->count(),
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}


