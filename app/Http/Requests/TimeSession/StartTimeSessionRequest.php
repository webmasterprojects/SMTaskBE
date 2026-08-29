<?php

namespace App\Http\Requests\TimeSession;

use Illuminate\Foundation\Http\FormRequest;

class StartTimeSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'type'           => ['required', 'in:travelling,working,break,other'],
            'start_time'     => ['nullable', 'date', 'before_or_equal:now'],
            'notes'          => ['nullable', 'string', 'max:1000'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ];
    }
}
