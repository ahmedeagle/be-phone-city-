<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name_ar' => $this->name_ar,
            'name_en' => $this->name_en,
            'address_ar' => $this->address_ar,
            'address_en' => $this->address_en,
            'city_ar' => $this->city_ar,
            'city_en' => $this->city_en,
            'latitude' => $this->latitude ? (float) $this->latitude : null,
            'longitude' => $this->longitude ? (float) $this->longitude : null,
            'google_maps_url' => $this->google_maps_url,
            'phone' => $this->phone,
            'phone2' => $this->phone2,
            'whatsapp' => $this->whatsapp,
            'working_hours_ar' => $this->working_hours_ar,
            'working_hours_en' => $this->working_hours_en,
        ];
    }
}
