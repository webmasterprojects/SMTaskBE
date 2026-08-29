<?php

namespace App\Http\Requests\TimeSession;

use Illuminate\Foundation\Http\FormRequest;

class EndTimeSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'end_time' => ['nullable', 'date'],
            'notes'    => ['nullable', 'string', 'max:1000'],
        ];
    }
}
