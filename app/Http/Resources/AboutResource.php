<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'about_website' => $this->about_website,
            'about_us' => $this->about_us,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'address' => $this->address,
            'maps' => $this->maps,
            'email' => $this->email,
            'phone' => $this->phone,
            'social_links' => $this->social_links ?? [],
        ];
    }
}

