<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Task;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetWorkHistory extends Model
{
    protected $table = 'asset_work_history';

    protected $fillable = [
        'asset_id', 'task_id', 'recorded_by', 'status', 'remarks', 'severity', 'resolution', 'images', 'recorded_at',
    ];

    protected $casts = [
        'images'      => 'array',
        'recorded_at' => 'datetime',
    ];

    public function asset(): BelongsTo      { return $this->belongsTo(Asset::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function task(): BelongsTo       { return $this->belongsTo(Task::class); }
}
