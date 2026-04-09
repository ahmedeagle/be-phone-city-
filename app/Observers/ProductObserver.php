<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\StockNotification;
use App\Notifications\BackInStockNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProductObserver
{
    /**
     * When product quantity is updated from 0 to > 0, notify subscribers.
     */
    public function updated(Product $product): void
    {
        if ($product->isDirty('quantity') && $product->getOriginal('quantity') <= 0 && $product->quantity > 0) {
            $this->notifySubscribers($product);
        }
    }

    protected function notifySubscribers(Product $product, $productOptionId = null): void
    {
        $subscribers = StockNotification::where('product_id', $product->id)
            ->where('product_option_id', $productOptionId)
            ->where('notified', false)
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        foreach ($subscribers as $subscriber) {
            try {
                if ($subscriber->user) {
                    $subscriber->user->notify(new BackInStockNotification($product));
                } else {
                    Notification::route('mail', $subscriber->email)
                        ->notify(new BackInStockNotification($product));
                }

                $subscriber->update([
                    'notified' => true,
                    'notified_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send back-in-stock notification', [
                    'subscriber_id' => $subscriber->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Back-in-stock notifications sent', [
            'product_id' => $product->id,
            'count' => $subscribers->count(),
        ]);
    }
}
