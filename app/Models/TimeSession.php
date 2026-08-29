<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSession extends Model
{
    protected $fillable = [
        'task_id', 'appointment_id', 'technician_id', 'recorded_by',
        'type', 'start_time', 'end_time', 'duration_mins', 'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function task(): BelongsTo        { return $this->belongsTo(Task::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function technician(): BelongsTo  { return $this->belongsTo(Technician::class); }
    public function recordedBy(): BelongsTo  { return $this->belongsTo(User::class, 'recorded_by'); }

    protected static function booted(): void
    {
        static::saving(function (TimeSession $session) {
            if ($session->end_time && $session->start_time) {
                $session->duration_mins = (int) round(
                    Carbon::parse($session->start_time)
                        ->diffInSeconds(Carbon::parse($session->end_time)) / 60
                );
            }
        });
    }
}
