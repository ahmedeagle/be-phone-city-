<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'notes' => $this->notes,
            'location' => $this->whenLoaded('location', function () {
                return new LocationResource($this->location);
            }),
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return new PaymentMethodResource($this->paymentMethod);
            }),
            'delivery_method' => $this->delivery_method,
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'discount_code' => $this->whenLoaded('discountCode', function () {
                return new DiscountResource($this->discountCode);
            }),
            'shipping' => (float) $this->shipping,
            // Tax is included in subtotal, hiding it from general API view as per request
            // 'tax' => (float) $this->tax,
            'points_discount' => (float) $this->points_discount,
            'total' => (float) $this->total,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'invoice' => $this->whenLoaded('invoice', function () {
                return new InvoiceResource($this->invoice);
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
