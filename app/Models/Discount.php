<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory, HasTranslations;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    // Condition Types
    public const CONDITION_NONE = null;
    public const CONDITION_FIRST_ORDER = 'first_order';
    public const CONDITION_MIN_AMOUNT = 'min_amount';
    public const CONDITION_MIN_QUANTITY = 'min_quantity';
    public const CONDITION_NEW_CUSTOMER = 'new_customer';

    public static function getConditionTypes(): array
    {
        return [
            self::CONDITION_NONE => 'بدون شرط',
            self::CONDITION_FIRST_ORDER => 'أول طلب',
            self::CONDITION_MIN_AMOUNT => 'الحد الأدنى للمبلغ',
            self::CONDITION_MIN_QUANTITY => 'الحد الأدنى للكمية',
            self::CONDITION_NEW_CUSTOMER => 'عميل جديد',
        ];
    }

    protected $fillable = [
        'code',
        'status',
        'start',
        'end',
        'description_en',
        'description_ar',
        'type',
        'value',
        'condition',
    ];

    protected $casts = [
        'status' => 'boolean',
        'start' => 'datetime',
        'end' => 'datetime',
        'value' => 'decimal:2',
        'condition' => 'array',
    ];

    protected $appends = ['description'];

    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->translate('description')
        );
    }
}
