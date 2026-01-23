<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class About extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'about_website_en',
        'about_website_ar',
        'about_us_en',
        'about_us_ar',
        'image',
        'address_ar',
        'address_en',
        'maps',
        'email',
        'phone',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array', // Automatically handles JSON encoding/decoding
    ];

    protected $appends = ['address', 'about_website', 'about_us'];

    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('address')
        );
    }

    protected function aboutWebsite(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('about_website')
        );
    }

    protected function aboutUs(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('about_us')
        );
    }
}
