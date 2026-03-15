<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'exists:users,email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف كبير وحرف صغير ورقم ورمز خاص (@$!%*#?&)',
        ];
    }
}
