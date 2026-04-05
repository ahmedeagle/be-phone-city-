<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^(05|5|\+9665|009665)[0-9]{8}$/', 'max:20'],
            'source' => ['nullable', 'string', 'in:popup,footer,checkout'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'رقم الجوال مطلوب',
            'phone.regex' => 'رقم الجوال غير صالح',
        ];
    }
}
