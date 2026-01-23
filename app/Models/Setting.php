<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'website_title_en',
        'website_title_ar',
        'logo',
        'free_shipping_threshold',
        'tax_percentage',
        'points_days_expired',
        'point_value',
        // Bank Account Details for Bank Transfer Payment
        'bank_name',
        'account_holder',
        'account_number',
        'iban',
        'swift_code',
        'branch',
        'bank_instructions',
        // New Arrivals and Featured Products Settings
        'show_new_arrivals_section',
        'show_featured_section',
        'new_arrivals_count',
        'featured_count',
    ];

    protected $casts = [
        'free_shipping_threshold' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'point_value' => 'decimal:2',
        'show_new_arrivals_section' => 'boolean',
        'show_featured_section' => 'boolean',
        'new_arrivals_count' => 'integer',
        'featured_count' => 'integer',
    ];

    /**
     * Get the singleton settings instance
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    /**
     * Get a setting value
     */
    public static function get(string $key, $default = null)
    {
        $settings = static::getSettings();
        return $settings->{$key} ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value): bool
    {
        $settings = static::getSettings();
        return $settings->update([$key => $value]);
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

}
