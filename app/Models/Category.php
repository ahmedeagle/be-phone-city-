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
        'excludes_madfu',
    ];

    protected $casts = [
        'is_trademark' => 'boolean',
        'is_bank_transfer' => 'boolean',
        'is_installment' => 'boolean',
        'is_madfu' => 'boolean',
        'excludes_madfu' => 'boolean',
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

    /**
     * IDs of every category where Madfu must not be offered: the ones flagged
     * excludes_madfu plus their whole subtree, so a brand added under a flagged
     * department inherits the exclusion without being flagged itself.
     *
     * Resolved in one query and memoised for the request; the table is tiny.
     */
    public static function madfuExcludedIds(): array
    {
        return once(function () {
            $all = static::query()->get(['id', 'parent_id', 'excludes_madfu']);
            $childrenOf = $all->groupBy('parent_id');

            $excluded = [];
            $pending = $all->where('excludes_madfu', true)->pluck('id')->all();

            while ($pending) {
                $id = array_pop($pending);

                // Guard against self-parented and cyclic rows, which exist in the data.
                if (isset($excluded[$id])) {
                    continue;
                }
                $excluded[$id] = true;

                foreach ($childrenOf->get($id, collect()) as $child) {
                    $pending[] = $child->id;
                }
            }

            return array_keys($excluded);
        });
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
