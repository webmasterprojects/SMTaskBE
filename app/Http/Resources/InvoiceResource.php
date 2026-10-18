<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'client_id'            => $this->client_id,
            'property_id'          => $this->property_id,
            'task_id'              => $this->task_id,
            'service_quote_id'     => $this->service_quote_id,
            'invoice_number'       => $this->invoice_number,
            'reference'            => $this->reference,
            'description_of_works' => $this->description_of_works,
            'date_issued'          => $this->date_issued?->toDateString(),
            'date_due'             => $this->date_due?->toDateString(),
            'status'               => $this->status,
            'line_items'           => $this->line_items ?? [],
            'client_details'       => $this->whenLoaded('client', fn () => $this->client ? [
                'id'              => $this->client->id,
                'name'            => $this->client->name,
                'email'           => $this->client->email,
                'billing_name'    => $this->client->billing_name,
                'billing_address' => $this->client->billing_address,
                'address'         => $this->client->address,
            ] : null),
            'property_details'     => $this->whenLoaded('property', fn () => $this->property ? [
                'id'      => $this->property->id,
                'name'    => $this->property->name,
                'address' => $this->property->formatted_address,
            ] : null),
            'task_details'         => $this->whenLoaded('task', fn () => $this->task ? [
                'id'   => $this->task->id,
                'name' => $this->task->name ?? ('Task #' . $this->task->id),
            ] : null),
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at,
        ];
    }
}
