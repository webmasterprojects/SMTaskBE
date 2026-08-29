<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'email'       => $this->email,
            'role'        => $this->role,
            'status'      => $this->status,
            'phone_number' => $this->phone_number,
            'permissions' => $this->permissions ?? [],
            'technician'  => $this->whenLoaded('technician', fn () => TechnicianResource::make($this->technician)),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
