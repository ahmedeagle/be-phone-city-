<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomePage extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'offer_text_en',
        'offer_text_ar',
        'offer_images',
        'app_title_en',
        'app_title_ar',
        'app_description_en',
        'app_description_ar',
        'app_main_image',
        'app_images',
        'main_images',
    ];

    protected $casts = [
        'offer_images' => 'array',
        'app_images' => 'array',
        'main_images' => 'array',
    ];

    protected $appends = ['offer_text', 'app_title', 'app_description', 'offer_images_for_display', 'app_images_for_display', 'main_images_for_display'];

    protected function offerText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('offer_text')
        );
    }

    protected function appTitle(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('app_title')
        );
    }

    protected function appDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('app_description')
        );
    }

    /**
     * Get offer images as collection of objects for RepeatableEntry
     */
    protected function offerImagesForDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $images = $this->offer_images ?? [];
                if (!is_array($images) || empty($images)) {
                    return [];
                }

                // Remove duplicates and filter empty values
                $images = array_values(array_unique(array_filter($images, fn($v) => !empty($v))));

                // Convert to array of objects for RepeatableEntry
                return array_map(function ($path) {
                    return ['path' => $path];
                }, $images);
            }
        );
    }

    /**
     * Get app images as collection of objects for RepeatableEntry
     */
    protected function appImagesForDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $images = $this->app_images ?? [];
                if (!is_array($images) || empty($images)) {
                    return [];
                }

                // Remove duplicates and filter empty values
                $images = array_values(array_unique(array_filter($images, fn($v) => !empty($v))));

                // Convert to array of objects for RepeatableEntry
                return array_map(function ($path) {
                    return ['path' => $path];
                }, $images);
            }
        );
    }

    /**
     * Get main images as collection of objects for RepeatableEntry
     */
    protected function mainImagesForDisplay(): Attribute
    {
        return Attribute::make(
            get: function () {
                $images = $this->main_images ?? [];
                if (!is_array($images) || empty($images)) {
                    return [];
                }

                // Remove duplicates and filter empty values
                $images = array_values(array_unique(array_filter($images, fn($v) => !empty($v))));

                // Convert to array of objects for RepeatableEntry
                return array_map(function ($path) {
                    return ['path' => $path];
                }, $images);
            }
        );
    }
}
