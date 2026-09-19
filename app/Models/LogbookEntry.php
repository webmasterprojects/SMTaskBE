<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogbookEntry extends Model
{
    protected $fillable = ['property_id', 'task_id', 'created_by', 'name', 'date', 'note', 'path', 'original_name', 'mime_type', 'size'];

    protected $casts = ['date' => 'date'];

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function task(): BelongsTo      { return $this->belongsTo(Task::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
