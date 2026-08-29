<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceQuoteAsset extends Model
{
    protected $fillable = [
        'service_quote_id', 'asset_id', 'label', 'variant', 'type', 'line_type',
        'serial', 'bar_code', 'make', 'size', 'model', 'quantity',
        'cost_price', 'markup', 'sales_price', 'gst', 'status', 'remarks',
    ];

    public function serviceQuote(): BelongsTo { return $this->belongsTo(ServiceQuote::class); }
    public function asset(): BelongsTo        { return $this->belongsTo(Asset::class); }
}
