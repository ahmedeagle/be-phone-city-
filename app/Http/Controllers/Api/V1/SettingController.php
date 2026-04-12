<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SettingResource;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    public function index(Request $request)
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Settings fetched successfully'),
            new SettingResource($settings)
        );
    }

    /**
     * Get specific setting value by key
     */
    public function show(Request $request, string $key)
    {
        $value = Setting::get($key);

        if ($value === null) {
            return Response::error(
                __('Setting not found'),
                null,
                404
            );
        }

        return Response::success(
            __('Setting fetched successfully'),
            [
                'key' => $key,
                'value' => $value,
            ]
        );
    }

    /**
     * Get website information settings
     */
    public function websiteInfo()
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Website information fetched successfully'),
            [
                'website_title_en' => $settings->website_title_en,
                'website_title_ar' => $settings->website_title_ar,
                'logo' => $settings->logo_url,
            ]
        );
    }

    /**
     * Get shipping and tax settings
     */
    public function shippingTax()
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Shipping and tax settings fetched successfully'),
            [
                'free_shipping_threshold' => (float) $settings->free_shipping_threshold,
                'min_items_for_free_shipping' => (int) $settings->min_items_for_free_shipping,
                'tax_percentage' => (float) $settings->tax_percentage,
            ]
        );
    }

    /**
     * Get points settings
     */
    public function points()
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Points settings fetched successfully'),
            [
                'points_days_expired' => (int) $settings->points_days_expired,
                'point_value' => (float) $settings->point_value,
            ]
        );
    }

    /**
     * Get bank account details for bank transfer payment
     */
    public function bankDetails()
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Bank account details fetched successfully'),
            [
                'bank_name' => $settings->bank_name,
                'account_holder' => $settings->account_holder,
                'account_number' => $settings->account_number,
                'iban' => $settings->iban,
                'swift_code' => $settings->swift_code,
                'branch' => $settings->branch,
                'bank_instructions' => $settings->bank_instructions,
            ]
        );
    }

    /**
     * Get new arrivals and featured products settings
     */
    public function productSections()
    {
        $settings = Setting::getSettings();

        return Response::success(
            __('Product sections settings fetched successfully'),
            [
                'show_new_arrivals_section' => (bool) $settings->show_new_arrivals_section,
                'show_featured_section' => (bool) $settings->show_featured_section,
                'new_arrivals_count' => (int) $settings->new_arrivals_count,
                'featured_count' => (int) $settings->featured_count,
            ]
        );
    }

    /**
     * Toggle maintenance mode
     */
    public function toggleMaintenanceMode(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $settings = Setting::getSettings();
        $settings->update([
            'maintenance_mode' => $request->boolean('enabled'),
        ]);

        return Response::success(
            $request->boolean('enabled') 
                ? __('Maintenance mode enabled successfully')
                : __('Maintenance mode disabled successfully'),
            [
                'maintenance_mode' => (bool) $settings->maintenance_mode,
            ]
        );
    }
}
