<?php

namespace App\Filament\Admin\Resources\HomePages\Pages;

use App\Filament\Admin\Resources\HomePages\HomePageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHomePage extends CreateRecord
{
    protected static string $resource = HomePageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Convert arrays of objects back to arrays of strings for database storage
        if (isset($data['offer_images']) && is_array($data['offer_images'])) {
            $data['offer_images'] = array_filter(array_map(function ($item) {
                return is_array($item) && isset($item['image']) ? $item['image'] : (is_string($item) ? $item : null);
            }, $data['offer_images']));
        }

        if (isset($data['app_images']) && is_array($data['app_images'])) {
            $data['app_images'] = array_filter(array_map(function ($item) {
                return is_array($item) && isset($item['image']) ? $item['image'] : (is_string($item) ? $item : null);
            }, $data['app_images']));
        }

        if (isset($data['main_images']) && is_array($data['main_images'])) {
            $data['main_images'] = array_filter(array_map(function ($item) {
                return is_array($item) && isset($item['image']) ? $item['image'] : (is_string($item) ? $item : null);
            }, $data['main_images']));
        }

        return $data;
    }
}
