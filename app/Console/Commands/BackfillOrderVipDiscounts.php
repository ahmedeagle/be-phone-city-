<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\User;
use App\Services\VipTierService;
use Illuminate\Console\Command;

class BackfillOrderVipDiscounts extends Command
{
    protected $signature = 'orders:backfill-vip {--order= : Backfill a specific order ID} {--dry-run : Show what would be updated without changing anything}';

    protected $description = 'Backfill VIP discount for existing orders that have vip_discount=0 but belong to VIP users';

    public function handle(VipTierService $vipTierService): int
    {
        $specificOrder = $this->option('order');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN - no changes will be made');
        }

        // Check if vip_discount column exists
        if (!\Illuminate\Support\Facades\Schema::hasColumn('orders', 'vip_discount')) {
            $this->error('❌ Column vip_discount does not exist on orders table!');
            $this->error('Run: php artisan migrate');
            return self::FAILURE;
        }

        if (!\Illuminate\Support\Facades\Schema::hasColumn('orders', 'vip_tier_label')) {
            $this->error('❌ Column vip_tier_label does not exist on orders table!');
            $this->error('Run: php artisan migrate');
            return self::FAILURE;
        }

        $this->info('✅ vip_discount and vip_tier_label columns exist');

        $query = Order::where(function ($q) {
            $q->where('vip_discount', 0)->orWhereNull('vip_discount');
        });

        if ($specificOrder) {
            $query->where('id', $specificOrder);
        }

        $orders = $query->with('user')->get();

        if ($orders->isEmpty()) {
            $this->info('No orders with vip_discount=0 found.');
            return self::SUCCESS;
        }

        $this->info("Found {$orders->count()} orders with vip_discount=0");

        $updated = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            $user = $order->user;
            if (!$user) {
                $this->warn("Order #{$order->id}: No user found, skipping");
                $skipped++;
                continue;
            }

            $tierKey = $user->vip_tier ?? 'regular';
            if ($tierKey === 'regular') {
                $skipped++;
                continue;
            }

            // Calculate what VIP discount should have been
            $subtotal = $order->items->sum('total');
            if ($subtotal <= 0) {
                $subtotal = $order->subtotal;
            }

            $vipData = $vipTierService->calculateVipDiscount($user, $subtotal);

            if ($vipData['amount'] <= 0) {
                $skipped++;
                continue;
            }

            $this->info("Order #{$order->id} (#{$order->order_number}): User {$user->id} ({$user->name}) - Tier: {$vipData['tier']} - VIP Discount: {$vipData['amount']} SAR");

            if (!$dryRun) {
                $order->update([
                    'vip_discount' => $vipData['amount'],
                    'vip_tier_at_order' => $vipData['tier'],
                    'vip_tier_label' => $vipData['tier_label_ar'],
                ]);

                // Recalculate total: subtract VIP discount
                $newTotal = $order->subtotal - $order->discount + $order->shipping - $order->points_discount - $vipData['amount'];
                $newTotal = max(0, $newTotal);
                $order->update(['total' => $newTotal]);

                $this->info("  → Updated: vip_discount={$vipData['amount']}, total={$newTotal}");
            }

            $updated++;
        }

        $this->newLine();
        $this->info("Done! Updated: {$updated}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
