<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetTypeVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'asset_type_id' => $this->asset_type_id,
            'name'          => $this->name,
            'price'         => $this->price,
        ];
    }
}
