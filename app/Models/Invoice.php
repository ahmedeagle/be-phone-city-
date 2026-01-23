<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    // Invoice Types
    public const TYPE_ORIGINAL = 'original';
    public const TYPE_CREDIT_NOTE = 'credit_note';
    public const TYPE_REFUND = 'refund';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'invoice_date',
        'invoice_pdf_path',
        'type',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = static::generateInvoiceNumber($invoice->type);
            }
            if (empty($invoice->invoice_date)) {
                $invoice->invoice_date = now()->toDateString();
            }
        });
    }

    /**
     * Generate unique invoice number
     * Format: INV-YYYYMMDD-XXXXXX (for original)
     * Format: CN-YYYYMMDD-XXXXXX (for credit note)
     * Format: RF-YYYYMMDD-XXXXXX (for refund)
     */
    protected static function generateInvoiceNumber(string $type = self::TYPE_ORIGINAL): string
    {
        $date = now()->format('Ymd');

        // Set prefix based on type
        $prefix = match($type) {
            self::TYPE_CREDIT_NOTE => 'CN-',
            self::TYPE_REFUND => 'RF-',
            default => 'INV-',
        };

        $fullPrefix = $prefix . $date . '-';

        // Get the last invoice number for today with same type
        $lastInvoice = static::where('invoice_number', 'like', $fullPrefix . '%')
            ->where('type', $type)
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $newNumber = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            // First invoice of the day
            $newNumber = '000001';
        }

        return $fullPrefix . $newNumber;
    }

    /**
     * Get the order that owns the invoice
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if invoice PDF exists
     */
    public function hasPdf(): bool
    {
        return !empty($this->invoice_pdf_path);
    }

    /**
     * Get invoice PDF path
     */
    public function getPdfPath(): ?string
    {
        return $this->invoice_pdf_path;
    }

    /**
     * Store invoice PDF path
     */
    public function setPdfPath(string $path): void
    {
        $this->update(['invoice_pdf_path' => $path]);
    }

    /**
     * Create a credit note for this invoice
     */
    public function createCreditNote(string $notes = null): self
    {
        return static::create([
            'order_id' => $this->order_id,
            'type' => self::TYPE_CREDIT_NOTE,
            'notes' => $notes ?? 'Credit note for invoice ' . $this->invoice_number,
        ]);
    }

    /**
     * Create a refund invoice
     */
    public function createRefund(string $notes = null): self
    {
        return static::create([
            'order_id' => $this->order_id,
            'type' => self::TYPE_REFUND,
            'notes' => $notes ?? 'Refund for invoice ' . $this->invoice_number,
        ]);
    }
}
