<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'task_id'        => $this->task_id,
            'technician_id'  => $this->technician_id,
            'technician'     => $this->whenLoaded('technician', fn () => TechnicianResource::make($this->technician)),
            'recorded_by'    => $this->recorded_by,
            'type'           => $this->type,
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'duration_mins'  => $this->duration_mins,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at,
        ];
    }
}
