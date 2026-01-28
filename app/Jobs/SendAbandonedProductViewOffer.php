<?php

namespace App\Jobs;

use App\Models\ProductView;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendAbandonedProductViewOffer implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ProductView $productView
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(WhatsAppService $whatsAppService): void
    {
        // Check if view still exists and hasn't been purchased
        $productView = $this->productView->fresh(['user', 'product']);

        if (!$productView || $productView->purchased || $productView->offer_sent) {
            Log::info('Skipping abandoned product view offer - already purchased or sent', [
                'product_view_id' => $productView?->id,
            ]);
            return;
        }

        // Check if user added product to cart or ordered it
        $user = $productView->user;
        $product = $productView->product;

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
            // User added to cart or ordered, mark as purchased
            $productView->markAsPurchased();
            Log::info('Product view marked as purchased - user added to cart or ordered', [
                'product_view_id' => $productView->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            return;
        }

        // Get applicable offer for the product
        $bestOffer = $product->getBestOffer();

        // Send WhatsApp message
        $sent = $whatsAppService->sendProductOffer($user, $product, $bestOffer);

        if ($sent) {
            $productView->markOfferSent();
            Log::info('Abandoned product view offer sent via WhatsApp', [
                'product_view_id' => $productView->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
                'offer_id' => $bestOffer?->id,
            ]);
        } else {
            Log::error('Failed to send abandoned product view offer via WhatsApp', [
                'product_view_id' => $productView->id,
                'user_id' => $user->id,
                'product_id' => $product->id,
            ]);
            throw new \Exception('Failed to send WhatsApp message');
        }
    }
}
