<?php

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'         => ['nullable', 'integer', 'exists:clients,id'],
            'name'              => ['required', 'string', 'max:255'],
            'formatted_address' => ['nullable', 'string', 'max:500'],
            'latitude'          => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'         => ['nullable', 'numeric', 'between:-180,180'],
            'place_id'          => ['nullable', 'string', 'max:255'],
            'access_schedule'   => ['nullable', 'string', 'max:255'],
            'access_procedure'  => ['nullable', 'string', 'max:2000'],
            'access_code'       => ['nullable', 'string', 'max:100'],
            'access_note'       => ['nullable', 'string', 'max:2000'],
            'status'                        => ['nullable', Rule::in(['draft', 'active', 'inactive'])],
            'safety_policy'                 => ['nullable', 'array'],
            'safety_policy.*.question'      => ['required_with:safety_policy', 'string', 'max:500'],
            'safety_policy.*.type'          => ['required_with:safety_policy', 'string', Rule::in(['yes_no', 'always_sometimes_never', 'text', 'checkbox'])],
            'safety_policy.*.required'      => ['required_with:safety_policy', 'boolean'],
        ];
    }
}
