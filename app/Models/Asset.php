<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'asset_type_id', 'asset_type_variant_id', 'created_by',
        'label', 'field_id', 'level', 'internal_reference', 'serial', 'bar_code',
        'make', 'size', 'model', 'quantity', 'base_date', 'installation_date',
        'internal_notes', 'tags', 'contractor', 'location',
        'standard_of_maintenance', 'standard_of_installation', 'standard_of_performance',
        'is_not_on_occupancy_permit', 'is_silent', 'is_active', 'extra_fields',
    ];

    protected $casts = [
        'tags'                       => 'array',
        'base_date'                  => 'date',
        'installation_date'          => 'date',
        'is_not_on_occupancy_permit' => 'boolean',
        'is_silent'                  => 'boolean',
        'is_active'                  => 'boolean',
        'extra_fields'               => 'array',
    ];

    public function property(): BelongsTo        { return $this->belongsTo(Property::class); }
    public function assetType(): BelongsTo        { return $this->belongsTo(AssetType::class); }
    public function assetTypeVariant(): BelongsTo { return $this->belongsTo(AssetTypeVariant::class); }
    public function createdBy(): BelongsTo        { return $this->belongsTo(User::class, 'created_by'); }
    public function workHistory(): HasMany         { return $this->hasMany(AssetWorkHistory::class); }
    public function routines(): HasMany            { return $this->hasMany(Routine::class); }
}
