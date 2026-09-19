<?php

namespace App\Http\Requests\Task;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'           => ['nullable', 'string', 'max:255'],
            'internal_note'   => ['nullable', 'string', 'max:5000'],
            'tags'            => ['nullable', 'array'],
            'technician_note' => ['nullable', 'string', 'max:2000'],
            'invoice_note'    => ['nullable', 'string', 'max:2000'],
            'logbook'         => ['nullable', 'array'],
            'status'          => ['nullable', 'string', Rule::in(Setting::where('group', 'task_status')->pluck('key')->toArray() ?: ['ready', 'scheduled', 'in_progress', 'on_hold', 'completed', 'cancelled', 'archived'])],
            'routine_ids'     => ['nullable', 'array'],
            'routine_ids.*'   => ['integer', 'exists:routines,id'],
            'asset_ids'            => ['nullable', 'array'],
            'asset_ids.*'          => ['integer', 'exists:assets,id'],
            'service_category_id'  => ['nullable', 'integer', 'exists:settings,id'],
        ];
    }
}
