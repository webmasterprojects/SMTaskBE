<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class TimeReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'from_date'     => ['nullable', 'date'],
            'to_date'       => ['nullable', 'date', 'after_or_equal:from_date'],
            'property_id'   => ['nullable', 'integer', 'exists:properties,id'],
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
            'task_type'     => ['nullable', 'in:routine,on_demand'],
            'session_type'  => ['nullable', 'in:travelling,working,break,other'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
