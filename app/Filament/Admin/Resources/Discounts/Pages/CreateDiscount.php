<?php

namespace App\Filament\Admin\Resources\Discounts\Pages;

use App\Filament\Admin\Resources\Discounts\DiscountResource;
use App\Models\Discount;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert condition_type and condition_value to condition JSON
        if (isset($data['condition_type']) && $data['condition_type'] !== null) {
            $condition = ['type' => $data['condition_type']];

            if (in_array($data['condition_type'], [Discount::CONDITION_MIN_AMOUNT, Discount::CONDITION_MIN_QUANTITY]) && isset($data['condition_value'])) {
                $condition['value'] = $data['condition_value'];
            }

            $data['condition'] = $condition;
        } else {
            $data['condition'] = null;
        }

        unset($data['condition_type'], $data['condition_value']);

        return $data;
    }
}
