<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SliderResource extends JsonResource
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
            'title' => $this->title,
            'title_en' => $this->title_en,
            'title_ar' => $this->title_ar,
            'description' => $this->description,
            'description_en' => $this->description_en,
            'description_ar' => $this->description_ar,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'button' => [
                'have_button' => $this->have_button ?? false,
                'button_text' => $this->button_text,
                'button_text_en' => $this->button_text_en,
                'button_text_ar' => $this->button_text_ar,
                'type' => $this->type, // 'page', 'offer', 'product', 'category'
                'url_slug' => $this->url_slug,
            ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
