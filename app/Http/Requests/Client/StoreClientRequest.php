<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'status'       => ['nullable', Rule::in(['active', 'inactive', 'archived'])],
            'notes'        => ['nullable', 'string', 'max:5000'],
        ];
    }
}
