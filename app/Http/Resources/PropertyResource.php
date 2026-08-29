<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'client_id'         => $this->client_id,
            'client'            => $this->whenLoaded('client', fn () => ClientResource::make($this->client)),
            'name'              => $this->name,
            'formatted_address' => $this->formatted_address,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'place_id'          => $this->place_id,
            'access_schedule'   => $this->access_schedule,
            'access_procedure'  => $this->access_procedure,
            'access_code'       => $this->access_code,
            'access_note'       => $this->access_note,
            'status'            => $this->status,
            'safety_policy'     => $this->safety_policy ?? [],
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
