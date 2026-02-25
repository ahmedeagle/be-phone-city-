<?php

namespace App\Http\Requests\Api\V1\Chatbot;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'in:en,ar'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'message.required' => __('validation.required', ['attribute' => __('Message')]),
            'message.string' => __('validation.string', ['attribute' => __('Message')]),
            'message.max' => __('validation.max.string', ['attribute' => __('Message'), 'max' => 2000]),
            'language.in' => __('validation.in', ['attribute' => __('Language')]),
        ];
    }
}
