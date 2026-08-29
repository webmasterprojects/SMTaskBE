<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'client_id', 'created_by', 'name', 'billing_type',
        'recurrence', 'contract_start_date', 'contract_finish_date',
        'first_invoice_date', 'next_invoice_date', 'review_date',
        'price_increase_policy', 'status',
    ];

    protected $casts = [
        'contract_start_date'  => 'date',
        'contract_finish_date' => 'date',
        'first_invoice_date'   => 'date',
        'next_invoice_date'    => 'date',
        'review_date'          => 'date',
    ];

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function client(): BelongsTo    { return $this->belongsTo(Client::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function lines(): HasMany       { return $this->hasMany(ContractLine::class); }
}
