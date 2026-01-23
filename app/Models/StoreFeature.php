<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreFeature extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'image',
    ];

    protected $appends = ['name', 'description'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('name')
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }
}
