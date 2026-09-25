<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'property_id'          => $this->property_id,
            'property'             => $this->whenLoaded('property', fn () => PropertyResource::make($this->property)),
            'type'                 => $this->type,
            'label'                => $this->label,
            'service_category_id'  => $this->service_category_id,
            'service_category'     => $this->whenLoaded('serviceCategory', fn () => [
                'id'         => $this->serviceCategory->id,
                'name'       => $this->serviceCategory->value,
                'identifier' => $this->serviceCategory->meta['identifier'] ?? null,
                'is_default' => (bool) ($this->serviceCategory->meta['is_default'] ?? false),
            ]),
            'identifier' => $this->whenLoaded('serviceCategory', function () {
                $cat  = $this->serviceCategory;
                $abbr = $cat ? strtoupper($cat->meta['identifier'] ?? substr($cat->value, 0, 3)) : null;
                $slug = $this->label ? '-' . preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($this->label))) : '';
                return 'T' . $this->id . '-P' . $this->property_id . $slug . ($abbr ? '-' . $abbr : '');
            }, 'T' . $this->id . '-P' . $this->property_id),
            'internal_note'   => $this->internal_note,
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
