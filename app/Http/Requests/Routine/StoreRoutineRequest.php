<?php

namespace App\Http\Requests\Routine;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoutineRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'property_id'  => ['required', 'integer', 'exists:properties,id'],
            'asset_id'     => ['required', 'integer', 'exists:assets,id'],
            'frequency'    => ['required', 'array', 'min:1'],
            'frequency.*'  => [Rule::in(['MONTHLY', 'QUARTERLY', 'SIX-MONTHLY', 'ANNUALLY', 'TWO-YEARLY', 'THREE-YEARLY', 'FIVE-YEARLY'])],
            'annual_date'  => ['required', 'date'],
            'start_date'   => ['required', 'date'],
            'end_date'     => ['nullable', 'date', 'after:start_date'],
            'status'       => ['nullable', Rule::in(['active', 'paused', 'archived'])],
        ];
    }
}
