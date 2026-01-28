<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\ProductView;
use App\Services\NotificationService;

class OrderObserver
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->notificationService->notifyOrderCreated($order);

        // Mark product views as purchased for all products in this order
        $this->markProductViewsAsPurchased($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('status')) {
            $this->notificationService->notifyOrderStatusChanged($order);
        }
    }

    /**
     * Mark product views as purchased for products in the order
     */
    protected function markProductViewsAsPurchased(Order $order): void
    {
        if (!$order->user_id) {
            return;
        }

        $productIds = $order->items()->pluck('product_id')->unique();

        if ($productIds->isEmpty()) {
            return;
        }

        // Mark all product views for this user and these products as purchased
        ProductView::where('user_id', $order->user_id)
            ->whereIn('product_id', $productIds)
            ->where('purchased', false)
            ->update(['purchased' => true]);
    }
}
