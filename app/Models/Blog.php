<?php

namespace App\Models;

use App\Traits\HasSlug;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Blog extends Model
{
    use HasFactory, HasTranslations, HasSlug;

    protected $fillable = [
        'admin_id',
        'slug',
        'featured_image',
        'title_en',
        'title_ar',
        'short_description_en',
        'short_description_ar',
        'content_en',
        'content_ar',
        'meta_description_en',
        'meta_description_ar',
        'meta_keywords_en',
        'meta_keywords_ar',
        'is_published',
        'published_at',
        'views_count',
        'allow_comments',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'views_count' => 'integer',
        'allow_comments' => 'boolean',
    ];

    protected $appends = [
        'title',
        'short_description',
        'content',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * Get the field name to use for slug generation.
     */
    protected function getSlugSourceField(): string
    {
        return 'title_en';
    }

    // Translation accessors
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

    protected function content(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('content')
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

    // Relationships
    /**
     * Get the admin/author who created this blog post.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get the author name (for backward compatibility and display).
     */
    public function getAuthorNameAttribute(): string
    {
        return $this->admin?->name ?? 'Unknown';
    }

    /**
     * Get all approved top-level comments for this blog post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all comments including unapproved ones (for admin use).
     */
    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get all images associated with this blog post.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    /**
     * Get the main/featured image.
     */
    public function mainImage()
    {
        return $this->images()->where('is_main', true)->first();
    }

    // Scopes
    /**
     * Scope a query to only include published blog posts.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope a query to only include draft blog posts.
     */
    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    /**
     * Scope a query to order by published date (newest first).
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to order by most viewed.
     */
    public function scopeMostViewed($query)
    {
        return $query->orderBy('views_count', 'desc');
    }

    // Helper methods
    /**
     * Increment the views count for this blog post.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Check if the blog post is published and visible.
     */
    public function isVisible(): bool
    {
        return $this->is_published &&
               ($this->published_at === null || $this->published_at <= now());
    }

    /**
     * Publish the blog post.
     */
    public function publish(): void
    {
        $this->update([
            'is_published' => true,
            'published_at' => $this->published_at ?? now(),
        ]);
    }

    /**
     * Unpublish the blog post (set as draft).
     */
    public function unpublish(): void
    {
        $this->update([
            'is_published' => false,
        ]);
    }
}
