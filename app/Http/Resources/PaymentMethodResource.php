<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
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
            'name' => $this->name,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'status' => $this->status,
            'is_bank_transfer' => (bool) $this->is_bank_transfer,
            'is_installment' => (bool) $this->is_installment,
            'gateway' => $this->gateway ?? null,
        ];
    }
}

