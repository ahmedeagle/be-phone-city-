<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\VipTierService;
use Illuminate\Console\Command;

class RecalculateVipTiers extends Command
{
    protected $signature = 'vip:recalculate {--user= : Recalculate for a specific user ID}';

    protected $description = 'Recalculate VIP tiers for all users (or a specific user) based on their completed orders';

    public function handle(VipTierService $vipTierService): int
    {
        $userId = $this->option('user');

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
