<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null,
            'cost' => (float) $this->cost,
            'estimated_days_ar' => $this->estimated_days_ar,
            'estimated_days_en' => $this->estimated_days_en,
        ];
    }
}
