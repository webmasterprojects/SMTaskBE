<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Billing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'property_id', 'created_by', 'name', 'reference',
        'abn_number', 'accounting_organisation', 'attention', 'email',
        'phone_number', 'bh_phone_number', 'ah_phone_number', 'fax_number',
        'postal_address', 'status',
    ];

    public function client(): BelongsTo    { return $this->belongsTo(Client::class); }
    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
