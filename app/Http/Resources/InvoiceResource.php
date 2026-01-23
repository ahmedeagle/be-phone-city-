<?php

namespace App\Http\Resources;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->whenLoaded('order');

        // Check if order is actually loaded (not MissingValue)
        $orderLoaded = $order && !is_a($order, \Illuminate\Http\Resources\MissingValue::class);

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date->toDateString(),
            'invoice_pdf_path' => $this->invoice_pdf_path ? asset('storage/' . $this->invoice_pdf_path) : null,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'notes' => $this->notes,

            // Order summary (for list view - quick access without loading full order)
            'order_number' => $orderLoaded ? $order->order_number : null,
            'order_id' => $this->order_id,
            'total_amount' => $orderLoaded ? (float) $order->total : null,

            // Full order details using OrderResource (when loaded)
            'order' => $this->whenLoaded('order', function () {
                return new OrderResource($this->order);
            }),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }

    /**
     * Get type label
     */
    protected function getTypeLabel(): string
    {
        return match($this->type) {
            Invoice::TYPE_CREDIT_NOTE => __('Credit Note'),
            Invoice::TYPE_REFUND => __('Refund'),
            default => __('Original Invoice'),
        };
    }
}
