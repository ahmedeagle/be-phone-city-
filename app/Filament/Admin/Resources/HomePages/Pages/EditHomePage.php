<?php

namespace App\Filament\Admin\Resources\HomePages\Pages;

use App\Filament\Admin\Resources\HomePages\HomePageResource;
use Filament\Resources\Pages\EditRecord;

class EditHomePage extends EditRecord
{
    protected static string $resource = HomePageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert JSON arrays of strings to arrays of objects for Repeater
        if (isset($data['offer_images']) && is_array($data['offer_images'])) {
            $data['offer_images'] = array_map(fn($path) => ['image' => $path], $data['offer_images']);
        }

        if (isset($data['app_images']) && is_array($data['app_images'])) {
            $data['app_images'] = array_map(fn($path) => ['image' => $path], $data['app_images']);
        }

        if (isset($data['main_images']) && is_array($data['main_images'])) {
            $data['main_images'] = array_map(fn($path) => ['image' => $path], $data['main_images']);
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
