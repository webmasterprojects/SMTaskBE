<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'full_name'    => $this->full_name,
            'email'        => $this->email,
            'phone_number' => $this->phone_number,
            'address'      => $this->address,
            'branch'       => $this->branch,
            'zone'         => $this->zone,
            'status'       => $this->status,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
