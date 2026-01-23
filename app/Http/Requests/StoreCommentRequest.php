<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Allow both authenticated and guest users
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = auth()->id();
        
        // If user is authenticated, user_id is required, guest fields are not
        // If user is not authenticated, guest_name and guest_email are required
        if ($userId) {
            return [
                'blog_id' => ['required', 'integer', 'exists:blogs,id'],
                'content' => ['required', 'string', 'max:5000'],
                'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            ];
        } else {
            return [
                'blog_id' => ['required', 'integer', 'exists:blogs,id'],
                'content' => ['required', 'string', 'max:5000'],
                'guest_name' => ['required', 'string', 'max:255'],
                'guest_email' => ['required', 'email', 'max:255'],
                'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            ];
        }
    }
}
