<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_id',
        'user_id',
        'guest_name',
        'guest_email',
        'content',
        'is_approved',
        'parent_id',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
    ];

    // Relationships
    /**
     * Get the blog post this comment belongs to.
     */
    public function blog(): BelongsTo
    {
        return $this->belongsTo(Blog::class);
    }

    /**
     * Get the user who made this comment (if authenticated).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent comment (for nested/reply comments).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get all reply comments (nested comments).
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get all approved reply comments.
     */
    public function approvedReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')
            ->where('is_approved', true)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get all images associated with this comment.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Accessors
    /**
     * Get the commenter's name (user name or guest name).
     */
    public function getCommenterNameAttribute(): string
    {
        return $this->user?->name ?? $this->guest_name ?? 'Anonymous';
    }

    /**
     * Get the commenter's email (user email or guest email).
     */
    public function getCommenterEmailAttribute(): ?string
    {
        return $this->user?->email ?? $this->guest_email;
    }

    /**
     * Check if this is a guest comment.
     */
    public function getIsGuestCommentAttribute(): bool
    {
        return $this->user_id === null;
    }

    /**
     * Check if this is a reply to another comment.
     */
    public function getIsReplyAttribute(): bool
    {
        return $this->parent_id !== null;
    }

    // Scopes
    /**
     * Scope a query to only include approved comments.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include pending comments.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope a query to only include top-level comments (not replies).
     */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope a query to only include reply comments.
     */
    public function scopeReplies($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope a query to order by newest first.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to order by oldest first.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // Helper methods
    /**
     * Approve this comment.
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    /**
     * Disapprove/reject this comment.
     */
    public function disapprove(): void
    {
        $this->update(['is_approved' => false]);
    }

    /**
     * Get the count of approved replies.
     */
    public function getApprovedRepliesCountAttribute(): int
    {
        return $this->approvedReplies()->count();
    }
}
