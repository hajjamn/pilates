<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManagedBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorizzazione ulteriore è nel controller (ruolo + ownership).
        return $this->user()?->hasAnyRole(['operatore', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'paid' => ['nullable', 'boolean'],
            'use_package' => ['nullable', 'boolean'],
            'user_package_id' => ['nullable', 'integer', 'exists:user_packages,id'],
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
