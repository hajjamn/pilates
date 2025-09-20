<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagedBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['operatore', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'paid' => ['nullable', 'boolean'],
            'use_package' => ['nullable', 'boolean'],
            'user_package_id' => ['nullable', 'integer', 'exists:user_packages,id'],

            // 🔹 nuovi opzionali per contabilità
            'paid_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'paid_at' => ['nullable', 'date'],
            'lesson_price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'paid' => filter_var($this->input('paid', false), FILTER_VALIDATE_BOOLEAN),
            'use_package' => filter_var($this->input('use_package', false), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
