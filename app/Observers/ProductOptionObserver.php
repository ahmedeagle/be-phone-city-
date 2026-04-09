<?php

namespace App\Observers;

use App\Models\ProductOption;
use App\Models\StockNotification;
use App\Notifications\BackInStockNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ProductOptionObserver
{
    /**
     * When option quantity is updated from 0 to > 0, notify subscribers.
     */
    public function updated(ProductOption $option): void
    {
        if ($option->isDirty('quantity') && $option->getOriginal('quantity') <= 0 && $option->quantity > 0) {
            $this->notifySubscribers($option);
        }
    }

    protected function notifySubscribers(ProductOption $option): void
    {
        $product = $option->product;

        $subscribers = StockNotification::where('product_id', $product->id)
            ->where('product_option_id', $option->id)
            ->where('notified', false)
            ->get();

        if ($subscribers->isEmpty()) {
            return;
        }

        foreach ($subscribers as $subscriber) {
            try {
                if ($subscriber->user) {
                    $subscriber->user->notify(new BackInStockNotification($product, $option));
                } else {
                    Notification::route('mail', $subscriber->email)
                        ->notify(new BackInStockNotification($product, $option));
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

        Log::info('Back-in-stock notifications sent for option', [
            'product_id' => $product->id,
            'option_id' => $option->id,
            'count' => $subscribers->count(),
        ]);
    }
}
