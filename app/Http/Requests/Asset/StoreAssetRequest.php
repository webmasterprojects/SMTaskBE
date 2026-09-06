<?php

namespace App\Http\Requests\Asset;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'property_id'           => ['required', 'integer', 'exists:properties,id'],
            'asset_type_id'         => ['required', 'integer', 'exists:asset_types,id'],
            'asset_type_variant_id' => ['nullable', 'integer', 'exists:asset_type_variants,id'],
            'label'                 => ['nullable', 'string', 'max:255'],
            'field_id'              => ['nullable', 'string', 'max:100'],
            'level'                 => ['nullable', 'string', 'max:100'],
            'internal_reference'    => ['nullable', 'string', 'max:255'],
            'serial'                => ['nullable', 'string', 'max:255'],
            'bar_code'              => ['nullable', 'string', 'max:255'],
            'make'                  => ['nullable', 'string', 'max:255'],
            'size'                  => ['nullable', 'string', 'max:100'],
            'model'                 => ['nullable', 'string', 'max:255'],
            'quantity'              => ['nullable', 'integer', 'min:1'],
            'base_date'             => ['nullable', 'date'],
            'installation_date'     => ['nullable', 'date'],
            'internal_notes'        => ['nullable', 'string', 'max:5000'],
            'tags'                  => ['nullable', 'array'],
            'contractor'            => ['nullable', 'string', 'max:255'],
            'location'              => ['nullable', 'string', 'max:500'],
            'is_not_on_occupancy_permit' => ['nullable', 'boolean'],
            'is_silent'             => ['nullable', 'boolean'],
            'is_active'             => ['nullable', 'boolean'],
            'extra_fields'          => ['nullable', 'array'],
        ];
    }
}
