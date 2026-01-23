<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'slug'                => $this->slug,
            'name'                => $this->name,
            'name_en'             => $this->name_en,
            'name_ar'             => $this->name_ar,
            'banner'              => $this->banner ? asset('storage/' . $this->banner) : null,
            'title'               => $this->title,
            'title_en'            => $this->title_en,
            'title_ar'            => $this->title_ar,
            'short_description'   => $this->short_description,
            'short_description_en' => $this->short_description_en,
            'short_description_ar' => $this->short_description_ar,
            'description'         => $this->description,
            'description_en'      => $this->description_en,
            'description_ar'      => $this->description_ar,
            'meta_description'    => $this->meta_description,
            'meta_description_en' => $this->meta_description_en,
            'meta_description_ar' => $this->meta_description_ar,
            'meta_keywords'       => $this->meta_keywords,
            'meta_keywords_en'    => $this->meta_keywords_en,
            'meta_keywords_ar'    => $this->meta_keywords_ar,
            'order'               => $this->order,
        ];
    }
}
