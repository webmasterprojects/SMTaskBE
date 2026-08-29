<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'created_by', 'name', 'formatted_address',
        'latitude', 'longitude', 'place_id', 'access_schedule',
        'access_procedure', 'access_code', 'access_note', 'status',
        'safety_policy',
    ];

    protected $casts = [
        'latitude'      => 'decimal:8',
        'longitude'     => 'decimal:8',
        'safety_policy' => 'array',
    ];

    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function assets(): HasMany      { return $this->hasMany(Asset::class); }
    public function routines(): HasMany    { return $this->hasMany(Routine::class); }
    public function tasks(): HasMany       { return $this->hasMany(Task::class); }
    public function contracts(): HasMany   { return $this->hasMany(Contract::class); }
    public function billings(): HasMany    { return $this->hasMany(Billing::class); }
    public function contacts(): HasMany    { return $this->hasMany(PropertyContact::class); }
}
