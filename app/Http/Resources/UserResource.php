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
            'role_id'     => $this->role_id,
            'status'      => $this->status,
            'phone_number' => $this->phone_number,
            'permissions' => $this->permissions ?? [],
            'linked_role' => $this->whenLoaded('linkedRole', fn () => $this->linkedRole ? [
                'id'          => $this->linkedRole->id,
                'name'        => $this->linkedRole->name,
                'slug'        => $this->linkedRole->slug,
                'permissions' => $this->linkedRole->permissions ?? [],
            ] : null),
            'technician'  => $this->whenLoaded('technician', fn () => TechnicianResource::make($this->technician)),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
