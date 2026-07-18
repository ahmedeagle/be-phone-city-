<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasSlug, HasTranslations;

    protected $fillable = [
        'name_en',
        'name_ar',
        'slug',
        'image',
        'icon',
        'parent_id',
        'is_trademark',
        'is_bank_transfer',
        'is_installment',
        'is_madfu',
    ];

    protected $casts = [
        'is_trademark' => 'boolean',
        'is_bank_transfer' => 'boolean',
        'is_installment' => 'boolean',
        'is_madfu' => 'boolean',
    ];

    protected $appends = ['name'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('name')
        );
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function offers()
    {
        return $this->morphToMany(Offer::class, 'offerable', 'offerables');
    }
}
