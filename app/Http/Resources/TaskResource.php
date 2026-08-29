<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'property_id'     => $this->property_id,
            'property'        => $this->whenLoaded('property', fn () => PropertyResource::make($this->property)),
            'type'            => $this->type,
            'label'           => $this->label,
            'tags'            => $this->tags ?? [],
            'technician_note' => $this->technician_note,
            'invoice_note'    => $this->invoice_note,
            'logbook'         => $this->logbook ?? [],
            'status'          => $this->status,
            'routines'        => $this->whenLoaded('routines', fn () => RoutineResource::collection($this->routines)),
            'assets'          => $this->whenLoaded('assets', fn () => AssetResource::collection($this->assets)),
            'appointments'    => $this->whenLoaded('appointments', fn () => AppointmentResource::collection($this->appointments)),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
