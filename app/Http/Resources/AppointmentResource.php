<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'task_id'         => $this->task_id,
            'summary'         => $this->summary,
            'category'        => $this->category,
            'notes'           => $this->notes,
            'start_date_time' => $this->start_date_time,
            'end_date_time'   => $this->end_date_time,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
            // Return user_id as the identifier so frontend select can match
            'technicians'     => $this->whenLoaded('technicians', function () {
                return $this->technicians->map(fn ($t) => [
                    'id'        => $t->user_id ?? $t->id,   // user ID (for dropdown match)
                    'uid'       => $t->user_id ?? $t->id,
                    'full_name' => $t->full_name,
                    'email'     => $t->email,
                ]);
            }),
        ];
    }
}
