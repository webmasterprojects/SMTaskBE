<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'property_id'       => $this->property_id,
            'client_id'         => $this->client_id,
            'task_id'           => $this->task_id,
            'billing_id'        => $this->billing_id,
            'is_defect_quote'   => $this->is_defect_quote,
            'description'       => $this->description,
            'review_date'       => $this->review_date,
            'expiry_date'       => $this->expiry_date,
            'supervisor_id'     => $this->supervisor_id,
            'sales_person_id'   => $this->sales_person_id,
            'tags'              => $this->tags ?? [],
            'scope_of_work'     => $this->scope_of_work,
            'terms_and_condition' => $this->terms_and_condition,
            'internal_note'     => $this->internal_note,
            'status'            => $this->status,
            'total_cost_price'  => $this->total_cost_price,
            'total_markup'      => $this->total_markup,
            'sub_total'         => $this->sub_total,
            'total_sales_price' => $this->total_sales_price,
            'total_gst'         => $this->total_gst,
            'total_amount'      => $this->total_amount,
            'total_profit'      => $this->total_profit,
            'total_quantity'    => $this->total_quantity,
            'quote_assets'      => $this->whenLoaded('quoteAssets'),
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
