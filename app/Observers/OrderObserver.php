<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductView;
use App\Models\Setting;
use App\Notifications\OrderCompletedReviewRequest;
use App\Services\NotificationService;
use App\Services\PointsService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(
        protected NotificationService $notificationService,
        protected PointsService $pointsService
    ) {}

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Do NOT notify or mark as purchased here
        // Wait until payment is confirmed (payment_status = PAID)
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        $statusChangedByUs = false;

        // Check if payment status changed to PAID
        if ($order->isDirty('payment_status')) {
            $originalPaymentStatus = $order->getOriginal('payment_status');
            $newPaymentStatus = $order->payment_status;

            // When payment becomes PAID, trigger the full post-payment flow
            if ($newPaymentStatus === Order::PAYMENT_STATUS_PAID
                && $originalPaymentStatus !== Order::PAYMENT_STATUS_PAID) {

                // === Always execute these regardless of auto-confirm setting ===

                // Decrement stock for every order item
                $this->decrementStock($order);

                // Send the "order created" notification NOW (after payment confirmed)
                $this->notificationService->notifyOrderCreated($order);

                // Award points from order items NOW (after payment confirmed)
                $this->pointsService->awardPointsFromOrder($order);

                // Mark product views as purchased NOW (after payment confirmed)
                $this->markProductViewsAsPurchased($order);

                // === Determine whether to auto-process or hold at confirmed ===

                $shouldAutoProcess = $this->shouldAutoProcess($order);

                Log::info('OrderObserver: Payment confirmed — determining auto-process', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'should_auto_process' => $shouldAutoProcess,
                    'payment_gateway' => $order->paymentMethod?->gateway ?? 'unknown',
                    'delivery_method' => $order->delivery_method,
                ]);

                if ($shouldAutoProcess) {
                    // Auto-confirm: Move to processing (ready for shipping / ready for pickup)
                    $order->status = Order::STATUS_PROCESSING;
                    $order->saveQuietly();
                    $statusChangedByUs = true;

                    // For store pickup orders, send "ready for pickup" notification immediately
                    if ($order->delivery_method === Order::DELIVERY_STORE_PICKUP) {
                        if ($order->user) {
                            $order->user->notify(new \App\Notifications\OrderNotification($order, 'ready_for_pickup'));
                        }
                    }

                    // OTO shipment is NOT auto-created here.
                    // Admin creates it manually from "جاهزة للشحن" page with branch selection.
                } else {
                    // Manual review required: Hold at confirmed status
                    $order->status = Order::STATUS_CONFIRMED;
                    $order->saveQuietly();
                    $statusChangedByUs = true;

                    Log::info('OrderObserver: Order held at confirmed — awaiting admin review', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                    ]);
                }
            }
        }

        // Check if order status changed to CANCELLED — restore stock
        if ($order->isDirty('status')) {
            $originalStatus = $order->getOriginal('status');
            $newStatus = $order->status;

            if ($newStatus === Order::STATUS_CANCELLED
                && $originalStatus !== Order::STATUS_CANCELLED) {
                // Only restore stock if the order was previously paid (stock was decremented)
                if ($order->payment_status === Order::PAYMENT_STATUS_PAID) {
                    $this->restoreStock($order);
                }
            }
        }

        // Handle status changes (only if status was changed in the original update, not by us)
        if ($order->isDirty('status') && !$statusChangedByUs) {
            $this->notificationService->notifyOrderStatusChanged($order);

            // When admin moves confirmed → processing, order appears in "جاهزة للشحن" page
            // Admin creates OTO shipment manually with branch selection from there.

            // Send review request email when order is completed
            if ($order->status === Order::STATUS_COMPLETED
                && $originalStatus !== Order::STATUS_COMPLETED
                && $order->user) {
                $this->sendReviewRequest($order);
            }
        } elseif ($statusChangedByUs) {
            // Manually trigger notification since we used saveQuietly
            $this->notificationService->notifyOrderStatusChanged($order);
        }
    }

    /**
     * Determine if the order should be auto-processed (confirmed → processing → OTO)
     * or held at confirmed for manual admin review.
     *
     * Bank transfers: Always auto-process (admin already approved the payment manually)
     * Electronic payments: Depends on the auto_confirm_electronic_payments setting
     */
    protected function shouldAutoProcess(Order $order): bool
    {
        $paymentMethod = $order->paymentMethod;
        $gateway = $paymentMethod?->gateway ?? '';

        // Bank transfer: admin already manually approved, so always auto-process
        if ($gateway === 'bank_transfer') {
            return true;
        }

        // Cash on delivery: auto-process (no payment confirmation needed)
        if ($gateway === 'cash_on_delivery' || $gateway === 'cod') {
            return true;
        }

        // BNPL gateways (Tabby, Tamara): payment confirmed by provider, always auto-process
        if (in_array($gateway, ['tabby', 'tamara'])) {
            return true;
        }

        // Other electronic payments: check admin setting
        return (bool) Setting::get('auto_confirm_electronic_payments', true);
    }

    /**
     * Handle automatic OTO shipping when order is ready for processing
     */
    protected function handleAutomaticOtoShipping(Order $order): void
    {
        // Only for home delivery orders without existing shipment
        if ($order->delivery_method !== Order::DELIVERY_HOME) {
            return;
        }

        if (!empty($order->oto_order_id) || $order->hasActiveShipment()) {
            return;
        }

        Log::info('OrderObserver: Starting automatic OTO shipping', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
        ]);

        try {
            $order->refresh();
            $order->load(['location.city', 'items.product', 'items.productOption', 'user']);

            if (!$order->location) {
                Log::warning('OrderObserver: No location — skipping OTO shipping', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            $shippingService = app(\App\Services\Shipping\OtoShippingService::class);
            $shipmentDto = $shippingService->createOrderAndShipment($order);
            $order->refresh();

            Log::info('OrderObserver: Automatic OTO shipping completed', [
                'order_id' => $order->id,
                'tracking_number' => $order->tracking_number,
                'oto_order_id' => $order->oto_order_id,
            ]);

        } catch (\App\Services\Shipping\Oto\Exceptions\OtoValidationException $e) {
            Log::warning('OrderObserver: OTO validation error — admin can create manually', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            app(\App\Services\NotificationService::class)->notifyShipmentCreationFailed($order, $e->getMessage());
        } catch (\App\Services\Shipping\Oto\Exceptions\OtoApiException $e) {
            Log::error('OrderObserver: OTO API error — admin can create manually', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            app(\App\Services\NotificationService::class)->notifyShipmentCreationFailed($order, $e->getMessage());
        } catch (\Exception $e) {
            Log::error('OrderObserver: Unexpected OTO error — admin can create manually', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            app(\App\Services\NotificationService::class)->notifyShipmentCreationFailed($order, $e->getMessage());
        }
    }

    /**
     * Decrement stock for each item in the order
     */
    protected function decrementStock(Order $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            if ($item->product_option_id) {
                ProductOption::where('id', $item->product_option_id)
                    ->decrement('quantity', $item->quantity);
            } else {
                Product::where('id', $item->product_id)
                    ->decrement('quantity', $item->quantity);
            }
        }
    }

    /**
     * Restore stock for each item in the order (when order is cancelled)
     */
    protected function restoreStock(Order $order): void
    {
        $items = $order->items()->get();

        foreach ($items as $item) {
            if ($item->product_option_id) {
                ProductOption::where('id', $item->product_option_id)
                    ->increment('quantity', $item->quantity);
            } else {
                Product::where('id', $item->product_id)
                    ->increment('quantity', $item->quantity);
            }
        }
    }

    /**
     * Send review request email for completed order
     */
    protected function sendReviewRequest(Order $order): void
    {
        if (!$order->user || !$order->user->email) {
            return;
        }

        // Get all unique products from order items
        $orderItems = $order->items()->with('product')->get();
        $products = $orderItems->map(function ($item) {
            return $item->product;
        })->filter()->unique('id')->values();

        if ($products->isEmpty()) {
            return;
        }

        // Send review request notification
        $order->user->notify(new OrderCompletedReviewRequest($order, $products));
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
