<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $fillable = [
        'banner',
        'slug',
        'name_en',
        'name_ar',
        'title_en',
        'title_ar',
        'short_description_en',
        'short_description_ar',
        'description_en',
        'description_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
        'order',
        'is_active',
        'can_delete',
    ];

    protected $appends = [
        'name',
        'title',
        'short_description',
        'description',
        'meta_description',
        'meta_keywords',
    ];

    // Accessors
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('name')
        );
    }

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('title')
        );
    }

    protected function shortDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('short_description')
        );
    }

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }

    protected function metaDescription(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('meta_description')
        );
    }

    protected function metaKeywords(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('meta_keywords')
        );
    }

    /**
     * Get the field name to use for slug generation.
     */
    protected function getSlugSourceField(): string
    {
        return 'name_en';
    }

    /**
     * Override the trait's boot method to add can_delete logic.
     * Only generate slugs for pages that can be deleted.
     */
    protected static function bootHasSlug()
    {
        static::creating(function ($page) {
            // Only generate slug if can_delete is not false and name_en exists
            if ($page->can_delete !== false && !empty($page->name_en)) {
                $page->generateSlugIfEmpty();
            }
        });

        static::updating(function ($page) {
            // Only generate slug if can_delete is not false and slug is empty
            if ($page->can_delete !== false && empty($page->slug) && !empty($page->name_en)) {
                $page->generateSlugIfEmpty();
            }

            // Prevent updating slug if can_delete = false
            if ($page->can_delete === false || ($page->getOriginal('can_delete') === false)) {
                // Restore original slug if someone tried to change it
                if ($page->isDirty('slug')) {
                    $page->slug = $page->getOriginal('slug');
                }
            }
        });
    }

    /**
     * Override getRouteKeyName to use slug, but keep backward compatibility.
     * The controller handles both ID and slug lookup.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Override delete to prevent deletion if can_delete = false
     */
    public function delete()
    {
        if ($this->can_delete === false) {
            return false; // or throw an exception if you prefer
        }

        return parent::delete();
    }
}
