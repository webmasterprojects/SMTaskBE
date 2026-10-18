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

    // Frequency → month interval map (mirrors frontend FREQ_MONTHS)
    private const FREQ_MONTHS = [
        'MONTHLY'      => 1,
        'QUARTERLY'    => 3,
        'SIX-MONTHLY'  => 6,
        'ANNUALLY'     => 12,
        'TWO-YEARLY'   => 24,
        'FIVE-YEARLY'  => 60,
    ];

    /**
     * Returns all frequencies that produce an occurrence in the given year/month.
     * Mirrors the frontend getDueFrequenciesInMonth() logic exactly.
     */
    public function dueFrequenciesInMonth(int $year, int $month): array
    {
        if (empty($this->frequency)) return [];
        $anchor = $this->annual_date ?? $this->start_date;
        if (!$anchor) return [];

        $start  = $this->start_date ? Carbon::parse($this->start_date) : Carbon::parse($anchor);
        $end    = $this->end_date   ? Carbon::parse($this->end_date)   : Carbon::now()->addYears(5);

        // Normalize: snake_case → UPPER-HYPHEN (e.g. six_monthly → SIX-MONTHLY)
        $normalize = fn (string $f): string => strtoupper(str_replace('_', '-', $f));

        $due = [];
        foreach ($this->frequency as $freq) {
            $normFreq = $normalize($freq);
            $interval = self::FREQ_MONTHS[$normFreq] ?? null;
            if (!$interval) continue;

            $cursor = Carbon::parse($anchor);

            // Step cursor back to or before start
            while ($cursor->gt($start)) {
                $cursor->subMonths($interval);
            }
            // Step cursor forward to at or after start
            while ($cursor->lt($start)) {
                $cursor->addMonths($interval);
            }

            $guard = 0;
            while ($cursor->lte($end) && $guard < 200) {
                if ($cursor->year === $year && ($cursor->month - 1) === $month) {
                    $due[] = $normFreq;
                    break;
                }
                $cursor->addMonths($interval);
                $guard++;
            }
        }

        return $due;
    }

    /**
     * Returns the single highest-priority (longest interval) frequency due in the given month,
     * or null if none are due.
     */
    public function activeFrequencyInMonth(int $year, int $month): ?string
    {
        $due = $this->dueFrequenciesInMonth($year, $month);
        if (empty($due)) return null;

        return collect($due)->sortByDesc(fn ($f) => self::FREQ_MONTHS[$f] ?? 0)->first();
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
