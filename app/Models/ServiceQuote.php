<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceQuote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by', 'property_id', 'client_id', 'task_id', 'billing_id',
        'is_defect_quote', 'description', 'review_date', 'expiry_date',
        'supervisor_id', 'sales_person_id', 'tags', 'scope_of_work',
        'terms_and_condition', 'internal_note', 'status',
        'total_cost_price', 'total_markup', 'sub_total', 'total_sales_price',
        'total_gst', 'total_amount', 'total_profit', 'total_quantity',
    ];

    protected $casts = [
        'tags'            => 'array',
        'is_defect_quote' => 'boolean',
        'review_date'     => 'date',
        'expiry_date'     => 'date',
    ];

    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
    public function property(): BelongsTo    { return $this->belongsTo(Property::class); }
    public function client(): BelongsTo      { return $this->belongsTo(Client::class); }
    public function task(): BelongsTo        { return $this->belongsTo(Task::class); }
    public function billing(): BelongsTo     { return $this->belongsTo(Billing::class); }
    public function supervisor(): BelongsTo  { return $this->belongsTo(User::class, 'supervisor_id'); }
    public function salesPerson(): BelongsTo { return $this->belongsTo(User::class, 'sales_person_id'); }
    public function quoteAssets(): HasMany   { return $this->hasMany(ServiceQuoteAsset::class); }
}
