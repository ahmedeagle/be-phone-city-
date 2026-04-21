<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'name_en',
        'name_ar',
        'image',
        'description_en',
        'description_ar',
        'status',
        'is_installment',
        'is_bank_transfer',
        'processing_fee_percentage',
        'gateway',
    ];

    protected $casts = [
        'processing_fee_percentage' => 'decimal:2',
        'is_installment' => 'boolean',
        'is_bank_transfer' => 'boolean',
    ];

    protected $appends = ['name', 'description'];

    protected array $translatable = [
        'name',
        'description',
    ];

    // return translated name
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('name')
        );
    }

    // return translated description
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }

    // Scope for active method
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBankTransfer($query)
    {
        return $query->where('is_bank_transfer', true);
    }

    public function scopeInstallmentOnly($query)
    {
        return $query->where('is_installment', true);
    }
}
