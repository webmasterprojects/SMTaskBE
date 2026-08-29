<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyContact extends Model
{
    protected $fillable = [
        'property_id', 'asset_type_id', 'name', 'phone', 'email', 'notes',
    ];

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function assetType(): BelongsTo { return $this->belongsTo(AssetType::class); }
}
