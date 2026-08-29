<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'is_active'      => $this->is_active,
            'tags'           => $this->tags ?? [],
            'category'       => $this->category,
            'sub_category'   => $this->sub_category,
            'classification'     => $this->classification,
            'default_frequency'  => $this->default_frequency,
            'variants'        => $this->whenLoaded('variants', fn () => AssetTypeVariantResource::collection($this->variants)),
            'failing_remarks' => $this->whenLoaded('failingRemarks', fn () => $this->failingRemarks->map(fn ($r) => [
                'id'         => $r->id,
                'uid'        => $r->uid,
                'remark'     => $r->remark,
                'severity'   => $r->severity,
                'resolution' => $r->resolution,
                'sort_order' => $r->sort_order,
            ])),
            'created_at'      => $this->created_at,
        ];
    }
}
