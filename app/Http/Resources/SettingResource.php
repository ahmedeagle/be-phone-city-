<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // Website Information
            'website_title_en' => $this->website_title_en,
            'website_title_ar' => $this->website_title_ar,
            'logo' => $this->logo_url,

            // Shipping and Tax Settings
            'free_shipping_threshold' => (float) $this->free_shipping_threshold,
            'tax_percentage' => (float) $this->tax_percentage,

            // Points Settings
            'points_days_expired' => (int) $this->points_days_expired,
            'point_value' => (float) $this->point_value,

            // Bank Account Details
            'bank_name' => $this->bank_name,
            'account_holder' => $this->account_holder,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'swift_code' => $this->swift_code,
            'branch' => $this->branch,
            'bank_instructions' => $this->bank_instructions,

            // Product Sections Settings
            'show_new_arrivals_section' => (bool) $this->show_new_arrivals_section,
            'show_featured_section' => (bool) $this->show_featured_section,
            'new_arrivals_count' => (int) $this->new_arrivals_count,
            'featured_count' => (int) $this->featured_count,
        ];
    }
}
