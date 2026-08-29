<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'summary'          => ['nullable', 'string', 'max:255'],
            'category'         => ['nullable', 'string', 'max:100'],
            'notes'            => ['nullable', 'string', 'max:5000'],
            'start_date_time'  => ['nullable', 'date'],
            'end_date_time'    => ['nullable', 'date', 'after_or_equal:start_date_time'],
            'technician_ids'   => ['nullable', 'array'],
            // Accept user IDs — find or create linked technician record
            'technician_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
