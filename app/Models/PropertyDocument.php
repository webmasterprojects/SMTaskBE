<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyDocument extends Model
{
    protected $fillable = ['property_id', 'created_by', 'name', 'path', 'original_name', 'mime_type', 'size'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
