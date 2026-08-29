<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id', 'created_by', 'summary', 'category',
        'notes', 'start_date_time', 'end_date_time',
    ];

    protected $casts = [
        'start_date_time' => 'datetime',
        'end_date_time'   => 'datetime',
    ];

    public function task(): BelongsTo      { return $this->belongsTo(Task::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(Technician::class, 'appointment_technicians');
    }
}
