<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    protected $simple = false;

    public function simple()
    {
        $this->simple = true;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $originalPrice = $this->getOriginalPrice();
        $finalPrice = $this->getFinalPrice();
        $bestOffer = $this->getBestOffer();

        // Simple mode - return only essential data
        if ($this->simple || $request->input('simple') === 'true') {
            return [
                'id' => $this->id,
                'type' => $this->type,
                'value' => $this->value,
                'original_price' => number_format($originalPrice, 2),
                'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
                'final_price' => number_format($finalPrice, 2),
                'quantity' => $this->quantity,
                'stock_status' => $this->stock_status,
                'sku' => $this->sku,
            ];
        }

        // Full mode with payment methods (same logic as ProductResource - bank transfer / installment filtering)
        $paymentMethods = $this->getPaymentMethods();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'original_price' => number_format($originalPrice, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
            'discount_amount' => $bestOffer || $this->discounted_price ? number_format($originalPrice - $finalPrice, 2) : null,
            'quantity' => $this->quantity,
            'stock_status' => $this->stock_status,
            'sku' => $this->sku,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'applied_offer' => $bestOffer ? new OfferResource($bestOffer) : null,
            'payment_methods' => $paymentMethods,
        ];
    }

    /**
     * Get payment methods with bank transfer / installment filtering based on product's category
     */
    protected function getPaymentMethods(): array
    {
        $product = $this->product;
        if (! $product) {
            return [];
        }

        $query = PaymentMethod::active();

        if ($product->isInBankTransferCategory()) {
            $query->bankTransfer();
        } elseif ($product->isInMadfuCategory()) {
            $query->madfu();
        } elseif ($product->isInInstallmentCategory()) {
            $query->installmentOnly()->where('is_madfu', false);
        } else {
            // Standard category: exclude bank transfer, madfu, and (if product doesn't support it) installment.
            $query->where('is_bank_transfer', false)
                  ->where('is_madfu', false);

            if (! $product->is_installment) {
                $query->where('is_installment', false);
            }
        }

        return $query->get()->map(function ($method) {
            return [
                'id' => $method->id,
                'name' => $method->name,
                'image' => $method->image ? asset('storage/'.$method->image) : asset('images/payment-placeholder.jpg'),
                'gateway' => $method->gateway ?? null,
                'is_madfu' => (bool) $method->is_madfu,
            ];
        })->values()->toArray();
    }

    /**
     * Get minimal data for order items (includes images and pricing for display)
     */
    public function forOrderItem(): array
    {
        $originalPrice = $this->getOriginalPrice();
        $finalPrice = $this->getFinalPrice();

        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'value_en' => $this->value_en,
            'value_ar' => $this->value_ar,
            'images' => ImageResource::collection($this->whenLoaded('images')),
            'original_price' => number_format($originalPrice, 2),
            'discounted_price' => $this->discounted_price ? number_format($this->discounted_price, 2) : null,
            'final_price' => number_format($finalPrice, 2),
        ];
    }
}
