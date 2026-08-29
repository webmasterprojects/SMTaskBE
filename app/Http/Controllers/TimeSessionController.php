<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeSession\EndTimeSessionRequest;
use App\Http\Requests\TimeSession\StartTimeSessionRequest;
use App\Http\Resources\TimeSessionResource;
use App\Models\Task;
use App\Models\Technician;
use App\Models\TimeSession;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class TimeSessionController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $sessions = $task->timeSessions()
            ->with('technician')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'sessions' => TimeSessionResource::collection($sessions),
            'totals'   => $task->timeTotals(),
        ]);
    }

    // GET /time-sessions/active  — current user's open session across all tasks
    public function myActive(): JsonResponse
    {
        $user       = auth()->user();
        $technician = Technician::where('user_id', $user->id)->first();
        if (! $technician) {
            return response()->json(['session' => null]);
        }

        $session = TimeSession::with(['task', 'technician'])
            ->where('technician_id', $technician->id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        return response()->json([
            'session' => $session
                ? array_merge(TimeSessionResource::make($session)->resolve(), [
                    'task_label'       => $session->task?->label,
                    'task_property_id' => $session->task?->property_id,
                ])
                : null,
        ]);
    }

    public function start(StartTimeSessionRequest $request, Task $task): JsonResponse
    {
        $user = auth()->user();

        // Auto-resolve (or create) the technician record linked to the logged-in user
        $technician = Technician::where('user_id', $user->id)->first();
        if (! $technician) {
            $technician = Technician::create([
                'user_id'      => $user->id,
                'created_by'   => $user->id,
                'full_name'    => $user->name,
                'email'        => $user->email,
                'phone_number' => $user->phone_number,
                'status'       => 'active',
            ]);
        }

        // Check for any open session this technician has on ANY task
        $conflicting = TimeSession::with('task')
            ->where('technician_id', $technician->id)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if ($conflicting) {
            $ageHours = $conflicting->start_time->diffInHours(now());

            if ($ageHours >= 12) {
                // Forgotten/stale — auto-close silently and continue
                $conflicting->update([
                    'end_time' => now(),
                    'notes'    => ($conflicting->notes ? $conflicting->notes . ' ' : '')
                                  . '[auto-closed after ' . $ageHours . 'h inactivity]',
                ]);
            } elseif ($conflicting->task_id === $task->id) {
                // Same task — already has active session
                return response()->json([
                    'message'        => 'You already have an active session on this task.',
                    'active_session' => TimeSessionResource::make($conflicting),
                ], 422);
            } else {
                // Different task — conflict: let frontend decide
                return response()->json([
                    'message'            => 'You have an active session on another task.',
                    'conflict'           => true,
                    'conflicting_session' => array_merge(
                        TimeSessionResource::make($conflicting)->resolve(),
                        ['task_label' => $conflicting->task?->label]
                    ),
                ], 409);
            }
        }

        $existing = $task->activeSession($technician->id);
        if ($existing) {
            return response()->json([
                'message'        => 'You already have an active session. End it before starting a new one.',
                'active_session' => TimeSessionResource::make($existing),
            ], 422);
        }

        // Auto-pick the latest appointment for this task if none specified
        $appointmentId = $request->appointment_id
            ?? $task->appointments()->orderByDesc('start_date_time')->value('id');

        $session = $task->timeSessions()->create([
            'appointment_id' => $appointmentId,
            'technician_id'  => $technician->id,
            'recorded_by'    => $user->id,
            'type'           => $request->type,
            'start_time'     => $request->start_time ?? now(),
            'notes'          => $request->notes,
        ]);

        // ready or scheduled → in_progress when technician starts travelling or working
        if (in_array($request->type, ['travelling', 'working']) &&
            in_array($task->status, ['ready', 'scheduled'])) {
            $task->update(['status' => 'in_progress']);
        }

        return response()->json(TimeSessionResource::make($session->load('technician')), 201);
    }

    public function end(EndTimeSessionRequest $request, Task $task, TimeSession $session): JsonResponse
    {
        if ($session->task_id !== $task->id) {
            abort(404, 'Session does not belong to this task.');
        }

        if ($session->end_time) {
            return response()->json(['message' => 'Session is already ended.'], 422);
        }

        $endTime = $request->end_time ?? now();

        if (Carbon::parse($endTime)->lt($session->start_time)) {
            return response()->json(['message' => 'End time cannot be before start time.'], 422);
        }

        $session->update([
            'end_time' => $endTime,
            'notes'    => $request->notes ?? $session->notes,
        ]);

        return response()->json(TimeSessionResource::make($session->fresh('technician')));
    }

    public function destroy(Task $task, TimeSession $session): JsonResponse
    {
        if ($session->task_id !== $task->id) {
            abort(404);
        }

        if (auth()->id() !== $session->recorded_by && auth()->user()->role !== 'admin') {
            abort(403, 'Cannot delete another user\'s time session.');
        }

        $session->delete();

        return response()->json(null, 204);
    }
}
