<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
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
            'points_count' => $this->points_count,
            'status' => $this->status,
            'expire_at' => $this->expire_at ? $this->expire_at->toISOString() : null,
            'used_at' => $this->used_at ? $this->used_at->toISOString() : null,
            'description' => $this->description,

            'product' => $this->whenLoaded('product', function () {
                if (!$this->product || is_a($this->product, \Illuminate\Http\Resources\MissingValue::class)) {
                    return null;
                }
                return [
                    'id' => $this->product->id,
                    'slug' => $this->product->slug,
                    'name' => $this->product->name,
                ];
            }),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
