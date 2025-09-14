<?php

namespace App\Http\Requests\Operator;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperatorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $auth = $this->user();
        $operator = $this->route('operator');

        return $auth && ($auth->hasRole('admin') || $auth->id === $operator->id);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $operatorId = $this->route('operator')->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($operatorId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date'],

            'roles' => [$this->user()?->hasRole('admin') ? 'array' : 'prohibited'],
            'roles.*' => [$this->user()?->hasRole('admin') ? 'string' : 'prohibited'],
        ];
    }

    public function prepareForValidation()
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => trim($this->input('phone')),
            ]);
        }
    }

    public function messages()
    {
        return [
            'email.unique' => 'Questa email è già in uso.',
            'roles.prohibited' => 'Non sei autorizzato a modificare i ruoli.'
        ];
    }
}
