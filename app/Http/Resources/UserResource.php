<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vipTierService = app(\App\Services\VipTierService::class);
        $nextTierProgress = $vipTierService->getNextTierProgress($this->resource);

        $tierKey = $this->vip_tier ?? 'regular';
        $tier = $vipTierService->findTierByKey($tierKey);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified' => !is_null($this->email_verified_at),
            'created_at' => $this->created_at->toISOString(),
            'vip_tier' => $tierKey,
            'vip_tier_discount' => (float) ($this->vip_tier_discount ?? 0),
            'vip_max_discount' => (float) ($this->vip_max_discount ?? 0),
            'vip_tier_label_ar' => $tier ? $tier->name_ar : \App\Services\VipTierService::REGULAR_LABEL['ar'],
            'vip_tier_label_en' => $tier ? $tier->name_en : \App\Services\VipTierService::REGULAR_LABEL['en'],
            'completed_orders_count' => (int) ($this->completed_orders_count ?? 0),
            'completed_orders_total' => (float) ($this->completed_orders_total ?? 0),
            'next_tier' => $nextTierProgress,
        ];
    }
}
