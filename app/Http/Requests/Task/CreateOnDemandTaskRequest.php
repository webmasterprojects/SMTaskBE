<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class CreateOnDemandTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'property_id'     => ['required', 'integer', 'exists:properties,id'],
            'asset_ids'       => ['required', 'array', 'min:1'],
            'asset_ids.*'     => ['integer', 'exists:assets,id'],
            'label'           => ['nullable', 'string', 'max:255'],
            'technician_note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
