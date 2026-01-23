<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'product' => $this->whenLoaded('product', function () {
                return $this->product ? (new ProductResource($this->product))->forOrderItem() : null;
            }),
            'product_option' => $this->whenLoaded('productOption', function () {
                return $this->productOption ? (new ProductOptionResource($this->productOption))->forOrderItem() : null;
            }),
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,
        ];
    }
}
