<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by', 'client_id', 'property_id', 'task_id', 'service_quote_id',
        'invoice_number', 'reference', 'description_of_works',
        'date_issued', 'date_due', 'status', 'line_items',
    ];

    protected $casts = [
        'line_items'  => 'array',
        'date_issued' => 'date',
        'date_due'    => 'date',
    ];

    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function client(): BelongsTo     { return $this->belongsTo(Client::class); }
    public function property(): BelongsTo   { return $this->belongsTo(Property::class); }
    public function task(): BelongsTo       { return $this->belongsTo(Task::class); }
    public function serviceQuote(): BelongsTo { return $this->belongsTo(ServiceQuote::class); }
}
