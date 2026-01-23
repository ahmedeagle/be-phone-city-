<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 * HasSlug Trait
 *
 * Provides automatic slug generation and management for Eloquent models.
 *
 * Usage:
 *
 * 1. Add 'slug' to your model's $fillable array
 * 2. Add 'slug' column to your migration
 * 3. Use the trait in your model:
 *
 *    use App\Traits\HasSlug;
 *
 *    class YourModel extends Model
 *    {
 *        use HasSlug;
 *
 *        // Optionally override the source field:
 *        protected function getSlugSourceField(): string
 *        {
 *            return 'name_en'; // or 'title', 'name', etc.
 *        }
 *    }
 *
 * Features:
 * - Auto-generates slug on model creation/update if slug is empty
 * - Uses slug as route key for model binding
 * - Ensures slug uniqueness automatically
 * - Provides regenerateSlug() method for manual regeneration
 *
 * @package App\Traits
 */
trait HasSlug
{
    /**
     * Boot the trait.
     * Auto-generates slug on model creation and update.
     */
    protected static function bootHasSlug()
    {
        static::creating(function ($model) {
            $model->generateSlugIfEmpty();
        });

        static::updating(function ($model) {
            $model->generateSlugIfEmpty();
        });
    }

    /**
     * Get the route key for the model.
     * Override this in your model if you don't want to use slug as route key.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the field name to use for slug generation.
     * Override this method in your model to use a different field.
     *
     * @return string
     */
    protected function getSlugSourceField(): string
    {
        // Try common field names in order of preference
        if (isset($this->name_en)) {
            return 'name_en';
        }

        if (isset($this->name)) {
            return 'name';
        }

        if (isset($this->title_en)) {
            return 'title_en';
        }

        if (isset($this->title)) {
            return 'title';
        }

        // Default fallback
        return 'name';
    }

    /**
     * Generate slug if it's empty and source field has value.
     */
    public function generateSlugIfEmpty(): void
    {
        $sourceField = $this->getSlugSourceField();

        if (empty($this->slug) && !empty($this->{$sourceField})) {
            $this->slug = $this->generateUniqueSlug($this->{$sourceField});
        }
    }

    /**
     * Generate a unique slug from a given string.
     *
     * @param string $value
     * @return string
     */
    public function generateUniqueSlug(string $value): string
    {
        $slug = Str::slug($value);

        // If slug is empty after conversion, use a fallback
        if (empty($slug)) {
            $slug = 'item-' . ($this->id ?? time());
        }

        // Truncate slug to fit database column (255 chars, leave room for uniqueness suffix)
        $maxLength = 250;
        if (mb_strlen($slug) > $maxLength) {
            $slug = mb_substr($slug, 0, $maxLength);
        }

        $originalSlug = $slug;
        $count = 1;

        // Ensure uniqueness
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $suffix = '-' . $count;
            $maxSlugLength = $maxLength - mb_strlen($suffix);
            $slug = mb_substr($originalSlug, 0, $maxSlugLength) . $suffix;
            $count++;
        }

        return $slug;
    }

    /**
     * Regenerate slug from source field.
     * Useful when you want to update slug manually.
     *
     * @return $this
     */
    public function regenerateSlug(): self
    {
        $sourceField = $this->getSlugSourceField();

        if (!empty($this->{$sourceField})) {
            $this->slug = $this->generateUniqueSlug($this->{$sourceField});
        }

        return $this;
    }
}
