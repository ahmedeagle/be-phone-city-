<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomePageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Process offer_images array to include full URLs
        $offerImages = [];
        if ($this->offer_images && is_array($this->offer_images)) {
            foreach ($this->offer_images as $image) {
                $offerImages[] = asset('storage/' . $image);
            }
        }

        // Process app_images array to include full URLs
        $appImages = [];
        if ($this->app_images && is_array($this->app_images)) {
            foreach ($this->app_images as $image) {
                $appImages[] = asset('storage/' . $image);
            }
        }

        // Process main_images array to include full URLs
        $mainImages = [];
        if ($this->main_images && is_array($this->main_images)) {
            foreach ($this->main_images as $image) {
                $mainImages[] = asset('storage/' . $image);
            }
        }

        return [
            'id' => $this->id,
            'offer_text' => $this->offer_text,
            'offer_text_en' => $this->offer_text_en,
            'offer_text_ar' => $this->offer_text_ar,
            'offer_images' => $offerImages,
            'app_title' => $this->app_title,
            'app_title_en' => $this->app_title_en,
            'app_title_ar' => $this->app_title_ar,
            'app_description' => $this->app_description,
            'app_description_en' => $this->app_description_en,
            'app_description_ar' => $this->app_description_ar,
            'app_main_image' => $this->app_main_image ? asset('storage/' . $this->app_main_image) : null,
            'app_images' => $appImages,
            'main_images' => $mainImages,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
