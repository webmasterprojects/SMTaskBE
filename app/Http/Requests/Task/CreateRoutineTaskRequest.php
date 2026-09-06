<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoutineTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'property_id'     => ['required', 'integer', 'exists:properties,id'],
            'routine_ids'     => ['nullable', 'array'],
            'routine_ids.*'   => ['integer', 'exists:routines,id'],
            'label'           => ['nullable', 'string', 'max:255'],
            'technician_note' => ['nullable', 'string', 'max:2000'],
            'invoice_note'    => ['nullable', 'string', 'max:2000'],
            'internal_note'   => ['nullable', 'string', 'max:5000'],
        ];
    }
}
