<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssetTypeFailingRemark extends Model
{
    protected $fillable = ['uid', 'asset_type_id', 'remark', 'severity', 'resolution', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uid ??= (string) Str::uuid());
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }
}
