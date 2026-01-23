<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    // Type constants
    const TYPE_SUPPORT = 'support';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_INQUIRY = 'inquiry';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_BILLING = 'billing';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'ticket_number',
        'user_id',
        'admin_id',
        'name',
        'email',
        'phone',
        'subject',
        'description',
        'status',
        'priority',
        'type',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Boot the model and generate ticket number.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    /**
     * Generate a unique ticket number.
     */
    protected static function generateTicketNumber(): string
    {
        do {
            $number = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        } while (static::where('ticket_number', $number)->exists());

        return $number;
    }

    // Relationships
    /**
     * Get the user who created this ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin/support agent handling this ticket.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Get all images associated with this ticket.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    // Scopes
    /**
     * Scope a query to only include pending tickets.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include in-progress tickets.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope a query to only include resolved tickets.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope a query to only include closed tickets.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    /**
     * Scope a query to only include open tickets (pending or in_progress).
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to order by priority (urgent first).
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')");
    }

    // Helper methods
    /**
     * Check if the ticket is open.
     */
    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if the ticket is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if the ticket is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Assign ticket to an admin.
     */
    public function assignTo(Admin $admin): void
    {
        $this->update([
            'admin_id' => $admin->id,
            'status' => self::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Mark ticket as resolved.
     */
    public function resolve(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Close the ticket.
     */
    public function close(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
        ]);
    }

    /**
     * Reopen a closed ticket.
     */
    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_PENDING,
            'resolved_at' => null,
        ]);
    }

    /**
     * Get status label in Arabic.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_IN_PROGRESS => 'قيد المعالجة',
            self::STATUS_RESOLVED => 'تم الحل',
            self::STATUS_CLOSED => 'مغلق',
            default => $this->status,
        };
    }

    /**
     * Get priority label in Arabic.
     */
    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'منخفض',
            self::PRIORITY_MEDIUM => 'متوسط',
            self::PRIORITY_HIGH => 'عالي',
            self::PRIORITY_URGENT => 'عاجل',
            default => $this->priority,
        };
    }

    /**
     * Get type label in Arabic.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_SUPPORT => 'دعم فني',
            self::TYPE_COMPLAINT => 'شكوى',
            self::TYPE_INQUIRY => 'استفسار',
            self::TYPE_TECHNICAL => 'تقني',
            self::TYPE_BILLING => 'فوترة',
            self::TYPE_OTHER => 'أخرى',
            default => $this->type,
        };
    }
}
