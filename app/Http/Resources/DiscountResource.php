<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'type' => $this->type,
            'value' => (float) $this->value,
            'description' => $this->description,
            'start' => $this->start->toISOString(),
            'end' => $this->end->toISOString(),
            'condition' => $this->condition,
        ];
    }
}

