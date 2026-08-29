<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TaskProduct extends Model
{
    protected $fillable = [
        'uid', 'task_id', 'created_by', 'asset_id', 'label', 'variant', 'type',
        'line_type', 'quantity', 'unit_price', 'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->uid ??= (string) Str::uuid());
    }

    public function task(): BelongsTo       { return $this->belongsTo(Task::class); }
    public function asset(): BelongsTo      { return $this->belongsTo(Asset::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
}
