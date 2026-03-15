<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ],
            'comment' => $this->comment,
            'rating' => $this->rating,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

