<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Technician extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'created_by', 'full_name', 'email', 'phone_number',
        'address', 'branch', 'zone', 'status',
    ];

    public function user(): BelongsTo       { return $this->belongsTo(User::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function timeSessions(): HasMany { return $this->hasMany(TimeSession::class); }

    public function appointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'appointment_technicians');
    }
}
