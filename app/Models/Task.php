<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Report;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'created_by', 'type', 'label',
        'tags', 'technician_note', 'invoice_note', 'logbook', 'status', 'internal_note',
        'service_category_id',
    ];

    protected $casts = [
        'tags'    => 'array',
        'logbook' => 'array',
    ];

    public function property(): BelongsTo         { return $this->belongsTo(Property::class); }
    public function serviceCategory(): BelongsTo  { return $this->belongsTo(\App\Models\Setting::class, 'service_category_id'); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function appointments(): HasMany   { return $this->hasMany(Appointment::class); }
    public function timeSessions(): HasMany   { return $this->hasMany(TimeSession::class); }
    public function serviceQuotes(): HasMany  { return $this->hasMany(ServiceQuote::class); }
    public function products(): HasMany       { return $this->hasMany(TaskProduct::class); }

    public function routines(): BelongsToMany
    {
        return $this->belongsToMany(Routine::class, 'task_routines');
    }

    public function reports(): HasMany      { return $this->hasMany(Report::class); }
    public function attachments(): HasMany    { return $this->hasMany(TaskAttachment::class); }
    public function logbookEntries(): HasMany { return $this->hasMany(\App\Models\LogbookEntry::class); }

    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'task_assets');
    }

    public function activeSession(int $technicianId): ?TimeSession
    {
        return $this->timeSessions()
            ->where('technician_id', $technicianId)
            ->whereNull('end_time')
            ->latest()
            ->first();
    }

    public function timeTotals(): array
    {
        $sessions = $this->timeSessions()->whereNotNull('end_time')->get();

        return [
            'travel_mins' => $sessions->where('type', 'travelling')->sum('duration_mins'),
            'work_mins'   => $sessions->where('type', 'working')->sum('duration_mins'),
            'break_mins'  => $sessions->where('type', 'break')->sum('duration_mins'),
            'other_mins'  => $sessions->where('type', 'other')->sum('duration_mins'),
            'total_mins'  => $sessions->sum('duration_mins'),
        ];
    }
}
