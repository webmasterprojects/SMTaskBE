<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Report extends Model
{
    protected $fillable = ['task_id', 'report_number', 'data', 'generated_by'];

    protected $casts = ['data' => 'array'];

    public function task(): BelongsTo           { return $this->belongsTo(Task::class); }
    public function generatedBy(): BelongsTo    { return $this->belongsTo(User::class, 'generated_by'); }
    public function emailLogs(): HasMany         { return $this->hasMany(ReportEmailLog::class); }
}
