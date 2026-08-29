<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;
    protected $fillable = ['group', 'key', 'value', 'meta', 'is_active', 'sort_order'];

    protected $casts = [
        'meta'      => 'array',
        'is_active' => 'boolean',
    ];
}
