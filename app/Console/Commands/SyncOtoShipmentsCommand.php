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
                            {--force : Force sync even for completed orders}
                            {--dry-run : Show what would be synced without actually syncing}';

    /**
     * The console command description
     */
    protected $description = 'Sync shipment status from OTO API for all active shipments';

    /**
     * Execute the console command
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No actual sync will be performed');
            $this->newLine();
        } else {
            $this->info('Starting OTO shipments sync...');
        }

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Build query for orders with active shipments
        $query = Order::query()
            ->where('shipping_provider', 'OTO')
            ->whereNotNull('oto_order_id') // We can sync if we have OTO ID, even if tracking is missing
            ->orderBy('shipping_status_updated_at', 'asc')
            ->orderBy('updated_at', 'asc');

        // Unless forced, only sync orders that are in transit or processing
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
            $this->newLine();
            $this->comment('Query conditions:');
            $this->comment('  - shipping_provider = OTO');
            $this->comment('  - oto_order_id IS NOT NULL');
            if (!$force) {
                $this->comment('  - status IN (processing, shipped, Delivery is in progress)');
            }
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} orders to sync.");
        $this->newLine();

        if ($dryRun) {
            // Show table of orders that would be synced
            $this->table(
                ['Order #', 'Status', 'Tracking #', 'Last Updated', 'OTO Order ID'],
                $orders->map(function ($order) {
                    return [
                        $order->order_number,
                        $order->status,
                        $order->tracking_number ?? 'MISSING (Will fetch)',
                        $order->shipping_status_updated_at 
                            ? $order->shipping_status_updated_at->format('Y-m-d H:i:s')
                            : 'Never',
                        $order->oto_order_id ?? 'N/A',
                    ];
                })->toArray()
            );
            $this->newLine();
            $this->info("✅ Would sync {$orders->count()} orders");
            $this->comment("Run without --dry-run to actually sync these orders.");
            return Command::SUCCESS;
        }

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

        $this->info("✅ Sync jobs dispatched: {$dispatched}");
        
        if ($skipped > 0) {
            $this->warn("⚠️  Skipped: {$skipped}");
        }

        Log::info('OTO shipments sync command completed', [
            'total_orders' => $orders->count(),
            'dispatched' => $dispatched,
            'skipped' => $skipped,
        ]);

        return Command::SUCCESS;
    }
}
