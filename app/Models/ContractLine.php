<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractLine extends Model
{
    protected $fillable = [
        'contract_id', 'routine_service', 'labels', 'product', 'price', 'description',
    ];

    protected $casts = [
        'labels' => 'array',
        'price'  => 'decimal:2',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
