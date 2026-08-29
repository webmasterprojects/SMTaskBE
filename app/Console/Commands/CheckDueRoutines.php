<?php

namespace App\Console\Commands;

use App\Models\Routine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CheckDueRoutines extends Command
{
    protected $signature   = 'routines:check-due {--days=7 : Days ahead to look for due routines}';
    protected $description = 'Flag routines that are due within the specified number of days';

    public function handle(): void
    {
        $daysAhead = (int) $this->option('days');
        $window    = now()->addDays($daysAhead);

        $routines = Routine::query()
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
            ->with(['asset', 'property'])
            ->get();

        $flagged = 0;

        foreach ($routines as $routine) {
            foreach ($routine->frequency as $freq) {
                $nextDue = $routine->nextDueDate($freq);

                if (! $nextDue || $nextDue->gt($window)) {
                    continue;
                }

                $exists = DB::table('task_routines')
                    ->join('tasks', 'task_routines.task_id', '=', 'tasks.id')
                    ->where('task_routines.routine_id', $routine->id)
                    ->whereIn('tasks.status', ['ready', 'scheduled', 'in_progress'])
                    ->whereNull('tasks.deleted_at')
                    ->exists();

                if (! $exists) {
                    Cache::put(
                        "due-routine:{$routine->id}:{$freq}",
                        [
                            'routine_id'  => $routine->id,
                            'property_id' => $routine->property_id,
                            'asset_id'    => $routine->asset_id,
                            'frequency'   => $freq,
                            'due_date'    => $nextDue->toDateString(),
                            'property'    => $routine->property?->name,
                            'asset'       => $routine->asset?->label,
                        ],
                        now()->addDays($daysAhead + 1)
                    );

                    $flagged++;
                }
            }
        }

        $this->info("Checked {$routines->count()} routines. Flagged {$flagged} as due.");
    }
}
