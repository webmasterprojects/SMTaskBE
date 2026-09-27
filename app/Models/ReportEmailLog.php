<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportEmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['report_id', 'to', 'subject', 'sent_by', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
