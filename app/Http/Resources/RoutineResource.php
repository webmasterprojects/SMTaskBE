<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoutineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'property_id' => $this->property_id,
            'asset_id'    => $this->asset_id,
            'asset'       => $this->whenLoaded('asset', fn () => AssetResource::make($this->asset)),
            'property'    => $this->whenLoaded('property', fn () => PropertyResource::make($this->property)),
            'frequency'   => $this->frequency,
            'annual_date' => $this->annual_date,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'status'      => $this->status,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
