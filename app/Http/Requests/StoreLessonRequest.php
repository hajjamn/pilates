<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['operatore', 'admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'starts_at' => ['required', 'date'], // ISO o formato accettato da Carbon
            'max_clients' => ['required', 'integer', 'min:1', 'max:200'],
            // per admin si può impostare operator_id; per operator il server lo forzerà
            'operator_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
