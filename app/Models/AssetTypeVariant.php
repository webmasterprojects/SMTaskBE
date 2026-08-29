<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTypeVariant extends Model
{
    protected $fillable = ['asset_type_id', 'name', 'price'];

    protected $casts = ['price' => 'decimal:2'];

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }
}
