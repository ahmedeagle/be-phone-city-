<?php

namespace App\Console\Commands;

use App\Models\ProductView;
use App\Jobs\SendAbandonedProductViewOffer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendAbandonedProductViewOffers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-views:send-offers
                            {--hours=1 : Number of hours after view to send offer}
                            {--dry-run : Run without actually sending messages}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp offers to users who viewed products but didn\'t purchase';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("Checking for product views older than {$hours} hour(s)...");

        // Get views that:
        // 1. Were viewed at least X hours ago
        // 2. Haven't received an offer yet
        // 3. Haven't been purchased
        $cutoffTime = Carbon::now()->subHours($hours);

        $productViews = ProductView::with(['user', 'product'])
            ->pendingOffer()
            ->notPurchased()
            ->where('viewed_at', '<=', $cutoffTime)
            ->get();

        if ($productViews->isEmpty()) {
            $this->info('No product views found that need offers.');
            return Command::SUCCESS;
        }

        $this->info("Found {$productViews->count()} product view(s) to process.");

        $sent = 0;
        $skipped = 0;

        foreach ($productViews as $productView) {
            // Double-check if user purchased or added to cart since view was created
            $user = $productView->user;
            $product = $productView->product;

            if (!$user || !$product) {
                $skipped++;
                continue;
            }

            // Check if product is in cart
            $inCart = $user->cartItems()
                ->where('product_id', $product->id)
                ->exists();

            // Check if user ordered this product
            $ordered = $user->orders()
                ->whereHas('items', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })
                ->exists();

            if ($inCart || $ordered) {
                // User purchased, mark as purchased
                $productView->markAsPurchased();
                $skipped++;
                $this->line("Skipped: User {$user->id} purchased product {$product->id}");
                continue;
            }

            if ($dryRun) {
                $this->line("Would send offer to user {$user->id} for product {$product->id}");
                $sent++;
            } else {
                // Dispatch job to send WhatsApp message
                SendAbandonedProductViewOffer::dispatch($productView)
                    ->delay(now()->addSeconds(5)); // Small delay to ensure view is marked

                $sent++;
                $this->line("Queued offer for user {$user->id} - product {$product->id}");
            }
        }

        $this->info("Processed: {$sent} sent, {$skipped} skipped");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No messages were actually sent');
        }

        return Command::SUCCESS;
    }
}
