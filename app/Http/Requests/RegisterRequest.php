<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Custom validation after the basic rules pass
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check if email exists and is verified
            $existingUserByEmail = \App\Models\User::where('email', $this->email)->first();
            if ($existingUserByEmail && $existingUserByEmail->email_verified_at) {
                $validator->errors()->add('email', __('The email has already been taken.'));
            }

            // Check if phone exists and is verified
            $existingUserByPhone = \App\Models\User::where('phone', $this->phone)->first();
            if ($existingUserByPhone && $existingUserByPhone->email_verified_at) {
                $validator->errors()->add('phone', __('The phone has already been taken.'));
            }
        });
    }
}
