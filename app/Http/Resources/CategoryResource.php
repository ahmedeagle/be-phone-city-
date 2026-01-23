<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'icon' => $this->icon ? asset('storage/' . $this->icon) : null,
            'parent_id' => $this->parent_id,
            'is_trademark' => $this->is_trademark ?? false,
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
