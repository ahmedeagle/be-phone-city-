<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'country' => $this->country,
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', function () {
                return $this->city ? [
                    'id' => $this->city->id,
                    'slug' => $this->city->slug,
                    'name' => $this->city->name,
                    'name_en' => $this->city->name_en,
                    'name_ar' => $this->city->name_ar,
                    'shipping_fee' => number_format($this->city->shipping_fee, 2),
                ] : null;
            }),
            'street_address' => $this->street_address,
            'national_address' => $this->national_address,
            'phone' => $this->phone,
            'email' => $this->email,
            'label' => $this->label,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
