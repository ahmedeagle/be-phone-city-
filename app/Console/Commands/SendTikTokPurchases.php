<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\TikTokPurchase;
use App\Services\TikTokService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendTikTokPurchases extends Command
{
    protected $signature = 'tiktok:send-purchases {--limit=100}';
    protected $description = 'Send consented, paid orders to TikTok and retry failed deliveries';

    public function handle(TikTokService $service): int
    {
        if (! $service->enabled()) {
            $this->info('TikTok is disabled or credentials are missing.');
            return self::SUCCESS;
        }
        TikTokPurchase::where('created_at', '<', now()->subDays(30))->whereNotNull('context')
            ->update(['context' => null, 'payload' => null]);
        $events = TikTokPurchase::whereNull('sent_at')->whereNotNull('context')
            ->where('attempts', '<', 12)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->whereHas('order', fn ($q) => $q->where('payment_status', Order::PAYMENT_STATUS_PAID)
                ->where('status', '!=', Order::STATUS_CANCELLED))
            ->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->get();
        $sent = 0;
        foreach ($events as $event) {
            Cache::lock('tiktok-purchase-'.$event->id, 60)->get(function () use ($event, $service, &$sent) {
                $fresh = $event->fresh();
                if ($fresh && $service->send($fresh)) {
                    $sent++;
                }
            });
        }
        $this->info("Sent {$sent} purchase events.");
        return self::SUCCESS;
    }
}
