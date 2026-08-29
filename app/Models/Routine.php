<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Routine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'asset_id', 'created_by',
        'frequency', 'annual_date', 'start_date', 'end_date', 'status',
    ];

    protected $casts = [
        'frequency'   => 'array',
        'annual_date' => 'date',
        'start_date'  => 'date',
        'end_date'    => 'date',
    ];

    public function property(): BelongsTo  { return $this->belongsTo(Property::class); }
    public function asset(): BelongsTo     { return $this->belongsTo(Asset::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_routines');
    }

    public function nextDueDate(string $freq): ?Carbon
    {
        $anchor = $this->annual_date;
        $now    = Carbon::now();

        return match ($freq) {
            'monthly'      => $now->copy()->day($anchor->day)->startOfDay(),
            'six_monthly'  => $this->nextSixMonthly($anchor, $now),
            'annually'     => $anchor->copy()->year($now->year)->when(
                fn ($d) => $d->isPast(),
                fn ($d) => $d->addYear()
            ),
            'two_yearly'   => $this->nextNYearly(2, $now),
            'three_yearly' => $this->nextNYearly(3, $now),
            'five_yearly'  => $this->nextNYearly(5, $now),
            default        => null,
        };
    }

    private function nextSixMonthly(Carbon $anchor, Carbon $now): Carbon
    {
        $d1 = $anchor->copy()->year($now->year);
        $d2 = $d1->copy()->addMonths(6);
        if ($d1->isFuture()) {
            return $d1;
        }
        if ($d2->isFuture()) {
            return $d2;
        }

        return $d1->addYear();
    }

    private function nextNYearly(int $n, Carbon $now): Carbon
    {
        $base = Carbon::parse($this->start_date);
        while ($base->lte($now)) {
            $base->addYears($n);
        }

        return $base;
    }
}
