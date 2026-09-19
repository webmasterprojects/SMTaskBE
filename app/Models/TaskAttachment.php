<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskAttachment extends Model
{
    protected $fillable = ['task_id', 'created_by', 'name', 'original_name', 'path', 'mime_type', 'size'];

    public function task(): BelongsTo     { return $this->belongsTo(Task::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getUrlAttribute(): string
    {
        return route('task-attachments.download', $this->id);
    }
}
