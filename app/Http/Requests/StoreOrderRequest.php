<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location_id' => [
                'nullable',
                'exists:locations,id',
                Rule::requiredIf(function () {
                    return $this->delivery_method === Order::DELIVERY_HOME;
                }),
            ],
            'payment_method_id' => 'required|exists:payment_methods,id',
            'delivery_method' => ['required', Rule::in([Order::DELIVERY_HOME, Order::DELIVERY_STORE_PICKUP])],
            'branch_id' => [
                'nullable',
                'exists:branches,id',
                Rule::requiredIf(function () {
                    return $this->delivery_method === Order::DELIVERY_STORE_PICKUP;
                }),
            ],
            'discount_code' => 'nullable|string|exists:discounts,code',
            'notes' => 'nullable|string|max:1000',
            'points_discount' => 'nullable|numeric|min:0',
            'use_point' => 'nullable|boolean',
            'shipping_company_id' => 'nullable|exists:shipping_companies,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'location_id.required' => __('Location is required for home delivery'),
            'payment_method_id.required' => __('Payment method is required'),
            'payment_method_id.exists' => __('Selected payment method does not exist'),
            'delivery_method.required' => __('Delivery method is required'),
            'delivery_method.in' => __('Invalid delivery method'),
            'branch_id.required' => __('Branch is required for store pickup'),
            'branch_id.exists' => __('Selected branch does not exist'),
            'discount_code.exists' => __('Invalid discount code'),
        ];
    }
}
