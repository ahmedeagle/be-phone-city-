<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    // Delivery Methods
    public const DELIVERY_HOME = 'home_delivery';
    public const DELIVERY_STORE_PICKUP = 'store_pickup';

    // Status Constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_IN_PROGRESS = 'Delivery is in progress';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Payment Status Constants
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_AWAITING_REVIEW = 'awaiting_review';
    public const PAYMENT_STATUS_PROCESSING = 'processing';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_CANCELLED = 'cancelled';
    public const PAYMENT_STATUS_EXPIRED = 'expired';
    public const PAYMENT_STATUS_REFUNDED = 'refunded';
    public const PAYMENT_STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    protected $fillable = [
        'user_id',
        'order_number',
        'notes',
        'location_id',
        'payment_method_id',
        'delivery_method',
        'subtotal',
        'discount',
        'discount_id',
        'shipping',
        'tax',
        'points_discount',
        'total',
        'status',
        'payment_status',
        'payment_transaction_id',
        'shipping_provider',
        'tracking_number',
        'tracking_status',
        'tracking_url',
        'shipping_reference',
        'shipping_eta',
        'shipping_status_updated_at',
        'shipping_payload',
        'oto_order_id',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'points_discount' => 'decimal:2',
        'total' => 'decimal:2',
        'shipping_payload' => 'array',
        'shipping_status_updated_at' => 'datetime',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });

        // Automatically create an invoice when an order is created
        static::created(function ($order) {
            $order->createInvoice();
        });
    }

    /**
     * Generate unique order number
     */
    protected static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(Str::random(8)) . '-' . now()->format('Ymd');
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get the user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the location for the order
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the payment method for the order
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the discount applied to the order
     */
    public function discountCode()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    /**
     * Get the order items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get all invoices for this order
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the original invoice for this order
     */
    public function invoice()
    {
        return $this->hasOne(Invoice::class)->where('type', Invoice::TYPE_ORIGINAL);
    }

    /**
     * Create an invoice for this order
     */
    public function createInvoice(string $notes = null): Invoice
    {
        return Invoice::create([
            'order_id' => $this->id,
            'type' => Invoice::TYPE_ORIGINAL,
            'notes' => $notes,
        ]);
    }

    /**
     * Check if order has an invoice
     */
    public function hasInvoice(): bool
    {
        return $this->invoice()->exists();
    }

    /**
     * Get the original invoice
     */
    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    /**
     * Get points earned from this order
     */
    public function points()
    {
        return $this->hasMany(Point::class);
    }

    /**
     * Get all payment transactions for this order
     */
    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Get the latest payment transaction
     */
    public function latestPaymentTransaction()
    {
        return $this->hasOne(PaymentTransaction::class)->latestOfMany();
    }

    /**
     * Get the current payment transaction
     */
    public function currentPaymentTransaction()
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    /**
     * Get the latest payment transaction (helper method)
     */
    public function getLatestPaymentTransaction(): ?PaymentTransaction
    {
        return $this->paymentTransactions()->latest()->first();
    }

    /**
     * Check if order can retry payment
     */
    public function canRetryPayment(): bool
    {
        // Cannot retry if payment is successful
        if ($this->hasSuccessfulPayment()) {
            return false;
        }

        // Can retry if payment status allows it
        return in_array($this->payment_status, [
            self::PAYMENT_STATUS_FAILED,
            self::PAYMENT_STATUS_CANCELLED,
            self::PAYMENT_STATUS_EXPIRED,
            self::PAYMENT_STATUS_PENDING,
        ]);
    }

    /**
     * Check if order has successful payment
     */
    public function hasSuccessfulPayment(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID;
    }

    /**
     * Check if payment is pending
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PENDING;
    }

    /**
     * Check if payment is awaiting admin review (for bank transfers)
     */
    public function isAwaitingPaymentReview(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_AWAITING_REVIEW;
    }

    /**
     * Check if payment failed
     */
    public function isPaymentFailed(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_FAILED;
    }

    /**
     * Check if payment is processing
     */
    public function isPaymentProcessing(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PROCESSING;
    }

    /**
     * Mark payment as paid
     */
    public function markPaymentAsPaid(PaymentTransaction $transaction = null): bool
    {
        $data = ['payment_status' => self::PAYMENT_STATUS_PAID];

        if ($transaction) {
            $data['payment_transaction_id'] = $transaction->id;
        }

        return $this->update($data);
    }

    /**
     * Mark payment as failed
     */
    public function markPaymentAsFailed(): bool
    {
        return $this->update(['payment_status' => self::PAYMENT_STATUS_FAILED]);
    }

    /**
     * Mark payment as processing
     */
    public function markPaymentAsProcessing(PaymentTransaction $transaction = null): bool
    {
        $data = ['payment_status' => self::PAYMENT_STATUS_PROCESSING];

        if ($transaction) {
            $data['payment_transaction_id'] = $transaction->id;
        }

        return $this->update($data);
    }

    /**
     * Mark payment as awaiting review
     */
    public function markPaymentAsAwaitingReview(PaymentTransaction $transaction = null): bool
    {
        $data = ['payment_status' => self::PAYMENT_STATUS_AWAITING_REVIEW];

        if ($transaction) {
            $data['payment_transaction_id'] = $transaction->id;
        }

        return $this->update($data);
    }

    /**
     * Get payment status display name
     */
    public function getPaymentStatusDisplayName(): string
    {
        return match($this->payment_status) {
            self::PAYMENT_STATUS_PENDING => __('Pending Payment'),
            self::PAYMENT_STATUS_AWAITING_REVIEW => __('Awaiting Review'),
            self::PAYMENT_STATUS_PROCESSING => __('Processing Payment'),
            self::PAYMENT_STATUS_PAID => __('Paid'),
            self::PAYMENT_STATUS_FAILED => __('Payment Failed'),
            self::PAYMENT_STATUS_CANCELLED => __('Payment Cancelled'),
            self::PAYMENT_STATUS_EXPIRED => __('Payment Expired'),
            self::PAYMENT_STATUS_REFUNDED => __('Refunded'),
            self::PAYMENT_STATUS_PARTIALLY_REFUNDED => __('Partially Refunded'),
            default => ucfirst($this->payment_status),
        };
    }

    /**
     * Check if order is within retry window (24 hours by default)
     */
    public function isWithinRetryWindow(int $hours = 24): bool
    {
        return $this->created_at->diffInHours(now()) < $hours;
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if order is confirmed
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Check if order is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    /**
     * Check if order is shipped
     */
    public function isShipped(): bool
    {
        return $this->status === self::STATUS_SHIPPED;
    }

    /**
     * Check if order is in progress (delivery)
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if order is delivered
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get status display name
     */
    public function getStatusDisplayName(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_CONFIRMED => __('Confirmed'),
            self::STATUS_PROCESSING => __('Processing'),
            self::STATUS_SHIPPED => __('Shipped'),
            self::STATUS_IN_PROGRESS => __('Delivery in Progress'),
            self::STATUS_DELIVERED => __('Delivered'),
            self::STATUS_COMPLETED => __('Completed'),
            self::STATUS_CANCELLED => __('Cancelled'),
            default => ucfirst($this->status),
        };
    }

    /**
     * Get all available statuses
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING,
            self::STATUS_SHIPPED,
            self::STATUS_IN_PROGRESS,
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Check if order is eligible for shipment creation
     */
    public function isEligibleForShipment(): bool
    {
        return $this->status === self::STATUS_PROCESSING
            && $this->delivery_method === self::DELIVERY_HOME
            && $this->location_id
            && empty($this->shipping_reference)
            && empty($this->tracking_number);
    }

    /**
     * Check if order has an active shipment
     */
    public function hasActiveShipment(): bool
    {
        return !empty($this->shipping_reference) || !empty($this->tracking_number);
    }

    /**
     * Check if order is being shipped (has tracking info)
     */
    public function isBeingShipped(): bool
    {
        return in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_IN_PROGRESS]);
    }
}
