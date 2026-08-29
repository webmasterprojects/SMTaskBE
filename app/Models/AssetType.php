<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['created_by', 'name', 'is_active', 'tags', 'category', 'sub_category', 'classification', 'default_frequency'];

    protected $casts = ['is_active' => 'boolean', 'tags' => 'array', 'default_frequency' => 'array'];

    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function variants(): HasMany     { return $this->hasMany(AssetTypeVariant::class); }
    public function assets(): HasMany       { return $this->hasMany(Asset::class); }
    public function failingRemarks(): HasMany { return $this->hasMany(AssetTypeFailingRemark::class)->orderBy('sort_order'); }
}
