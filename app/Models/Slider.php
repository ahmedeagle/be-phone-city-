<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'image',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'have_button',
        'button_text_en',
        'button_text_ar',
        'type',
        'url_slug',
    ];

    protected $casts = [
        'have_button' => 'boolean',
    ];

    protected $appends = ['title', 'description', 'button_text'];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('title')
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }

    protected function buttonText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('button_text')
        );
    }
}
