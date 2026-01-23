<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    // Status Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    // Gateway Constants
    public const GATEWAY_CASH = 'cash';
    public const GATEWAY_BANK_TRANSFER = 'bank_transfer';
    public const GATEWAY_TAMARA = 'tamara';
    public const GATEWAY_TABBY = 'tabby';
    public const GATEWAY_AMWAL = 'amwal';

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'gateway',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'request_payload',
        'response_payload',
        'error_message',
        'payment_proof_path',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'reviewed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the order that owns the transaction
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment method used
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the admin who reviewed this transaction
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope to get successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to get failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get processing transactions
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    /**
     * Scope to get transactions awaiting review (bank transfers)
     */
    public function scopeAwaitingReview($query)
    {
        return $query->where('gateway', self::GATEWAY_BANK_TRANSFER)
                     ->whereIn('status', [self::STATUS_PENDING, self::STATUS_PROCESSING])
                     ->whereNotNull('payment_proof_path')
                     ->whereNull('reviewed_at');
    }

    /**
     * Scope to get latest transaction for an order
     */
    public function scopeLatestForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId)
                     ->latest('created_at');
    }

    /**
     * Check if transaction is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if transaction failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if transaction can be retried
     */
    public function canRetry(): bool
    {
        return in_array($this->status, [
            self::STATUS_FAILED,
            self::STATUS_CANCELLED,
            self::STATUS_EXPIRED,
        ]);
    }

    /**
     * Check if transaction is expired
     */
    public function isExpired(): bool
    {
        if ($this->status === self::STATUS_EXPIRED) {
            return true;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Check if transaction has payment proof uploaded
     */
    public function hasPaymentProof(): bool
    {
        return !empty($this->payment_proof_path);
    }

    /**
     * Check if transaction has been reviewed
     */
    public function isReviewed(): bool
    {
        return !empty($this->reviewed_at);
    }

    /**
     * Check if transaction requires admin review
     */
    public function requiresReview(): bool
    {
        return $this->gateway === self::GATEWAY_BANK_TRANSFER
               && $this->hasPaymentProof()
               && !$this->isReviewed();
    }

    /**
     * Get payment proof full path
     */
    public function getPaymentProofUrl(): ?string
    {
        if (!$this->payment_proof_path) {
            return null;
        }

        return \Storage::url($this->payment_proof_path);
    }

    /**
     * Mark transaction as successful
     */
    public function markAsSuccessful(): bool
    {
        return $this->update(['status' => self::STATUS_SUCCESS]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $errorMessage = null): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark transaction as expired
     */
    public function markAsExpired(): bool
    {
        return $this->update(['status' => self::STATUS_EXPIRED]);
    }

    /**
     * Get gateway display name
     */
    public function getGatewayDisplayName(): string
    {
        return match($this->gateway) {
            self::GATEWAY_CASH => __('Cash on Delivery'),
            self::GATEWAY_BANK_TRANSFER => __('Bank Transfer'),
            self::GATEWAY_TAMARA => __('Tamara'),
            self::GATEWAY_TABBY => __('Tabby'),
            self::GATEWAY_AMWAL => __('Amwal'),
            default => ucfirst($this->gateway),
        };
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_SUCCESS => __('Success'),
            self::STATUS_FAILED => __('Failed'),
            self::STATUS_EXPIRED => __('Expired'),
            self::STATUS_CANCELLED => __('Cancelled'),
            self::STATUS_REFUNDED => __('Refunded'),
            self::STATUS_PARTIALLY_REFUNDED => __('Partially Refunded'),
            default => ucfirst($this->status),
        };
    }
}
