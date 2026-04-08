<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VipTierService;
use Illuminate\Console\Command;

class RecalculateVipTiers extends Command
{
    protected $signature = 'vip:recalculate {--user= : Recalculate for a specific user ID} {--debug : Show diagnostic info}';

    protected $description = 'Recalculate VIP tiers for all users (or a specific user) based on their completed orders';

    public function handle(VipTierService $vipTierService): int
    {
        $userId = $this->option('user');
        $debug = $this->option('debug');

        // Show diagnostic info
        if ($debug) {
            $tiers = \App\Models\VipTier::where('is_active', true)->orderBy('sort_order')->get();
            if ($tiers->isEmpty()) {
                $this->error('⚠ No active VIP tiers found in vip_tiers table! Run migrations first.');
                return self::FAILURE;
            }
            $this->info('Active VIP tiers:');
            $this->table(
                ['Key', 'Name', 'Min Orders', 'Min Total', 'Discount%', 'Max Discount'],
                $tiers->map(fn ($t) => [$t->key, $t->name_en, $t->min_orders, $t->min_total, $t->discount_percentage.'%', $t->max_discount])
            );

            // Show order status distribution
            $statuses = \App\Models\Order::selectRaw('status, payment_status, COUNT(*) as cnt, SUM(total) as total_sum')
                ->groupBy('status', 'payment_status')
                ->get();
            $this->newLine();
            $this->info('Order distribution by status & payment_status:');
            $this->table(
                ['Status', 'Payment Status', 'Count', 'Total Sum'],
                $statuses->map(fn ($s) => [$s->status, $s->payment_status, $s->cnt, number_format($s->total_sum, 2)])
            );

            // Show top users by order count
            $topUsers = \App\Models\Order::whereIn('status', ['delivered', 'completed'])
                ->where('payment_status', 'paid')
                ->selectRaw('user_id, COUNT(*) as cnt, SUM(total) as total_sum')
                ->groupBy('user_id')
                ->orderByDesc('total_sum')
                ->limit(10)
                ->get();
            $this->newLine();
            $this->info('Top 10 users (delivered/completed + paid):');
            $this->table(
                ['User ID', 'Orders', 'Total Spent'],
                $topUsers->map(fn ($u) => [$u->user_id, $u->cnt, number_format($u->total_sum, 2)])
            );

            if ($topUsers->isEmpty()) {
                $this->warn('No orders with status delivered/completed AND payment_status=paid found!');
                $this->newLine();
                // Check if orders exist with just delivered/completed
                $altUsers = \App\Models\Order::whereIn('status', ['delivered', 'completed'])
                    ->selectRaw('user_id, payment_status, COUNT(*) as cnt, SUM(total) as total_sum')
                    ->groupBy('user_id', 'payment_status')
                    ->orderByDesc('total_sum')
                    ->limit(10)
                    ->get();
                $this->info('Orders with delivered/completed (any payment_status):');
                $this->table(
                    ['User ID', 'Payment Status', 'Orders', 'Total'],
                    $altUsers->map(fn ($u) => [$u->user_id, $u->payment_status, $u->cnt, number_format($u->total_sum, 2)])
                );
            }

            $this->newLine();
        }

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User #{$userId} not found.");
                return self::FAILURE;
            }
            $vipTierService->recalculate($user);
            $this->info("Recalculated VIP tier for user #{$userId}: {$user->fresh()->vip_tier}");
            return self::SUCCESS;
        }

        $count = User::count();
        $this->info("Recalculating VIP tiers for {$count} users...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;
        User::chunk(100, function ($users) use ($vipTierService, &$updated, $bar) {
            foreach ($users as $user) {
                $oldTier = $user->vip_tier;
                $vipTierService->recalculate($user);
                if ($user->fresh()->vip_tier !== ($oldTier ?? 'regular')) {
                    $updated++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Done! {$updated} users had their VIP tier updated.");

        return self::SUCCESS;
    }
}
