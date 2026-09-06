<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'property_id'                => $this->property_id,
            'asset_type_id'              => $this->asset_type_id,
            'asset_type_variant_id'      => $this->asset_type_variant_id,
            'asset_type'                 => $this->whenLoaded('assetType', fn () => AssetTypeResource::make($this->assetType)),
            'asset_type_variant'         => $this->whenLoaded('assetTypeVariant', fn () => AssetTypeVariantResource::make($this->assetTypeVariant)),
            'label'                      => $this->label,
            'field_id'                   => $this->field_id,
            'level'                      => $this->level,
            'internal_reference'         => $this->internal_reference,
            'serial'                     => $this->serial,
            'bar_code'                   => $this->bar_code,
            'make'                       => $this->make,
            'size'                       => $this->size,
            'model'                      => $this->model,
            'quantity'                   => $this->quantity,
            'base_date'                  => $this->base_date,
            'installation_date'          => $this->installation_date,
            'internal_notes'             => $this->internal_notes,
            'tags'                       => $this->tags ?? [],
            'contractor'                 => $this->contractor,
            'location'                   => $this->location,
            'standard_of_maintenance'    => $this->standard_of_maintenance,
            'standard_of_installation'   => $this->standard_of_installation,
            'standard_of_performance'    => $this->standard_of_performance,
            'is_not_on_occupancy_permit' => $this->is_not_on_occupancy_permit,
            'is_silent'                  => $this->is_silent,
            'is_active'                  => $this->is_active,
            'extra_fields'               => $this->extra_fields ?? [],
            'created_at'                 => $this->created_at,
            'updated_at'                 => $this->updated_at,
        ];
    }
}
