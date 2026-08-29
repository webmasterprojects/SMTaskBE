<?php

namespace App\Http\Controllers;

use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Technician;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function index(Task $task): JsonResponse
    {
        $appointments = $task->appointments()
            ->with('technicians')
            ->orderBy('start_date_time')
            ->get();

        return response()->json(AppointmentResource::collection($appointments));
    }

    public function store(StoreAppointmentRequest $request, Task $task): JsonResponse
    {
        $appointment = $task->appointments()->create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        if ($request->technician_ids) {
            $technicianIds = $this->resolveTechnicianIds($request->technician_ids);
            $appointment->technicians()->sync($technicianIds);
        }

        // Auto-advance task status: ready → scheduled
        if ($task->status === 'ready') {
            $task->update(['status' => 'scheduled']);
        }

        return response()->json(AppointmentResource::make($appointment->load('technicians')), 201);
    }

    public function show(Appointment $appointment): JsonResponse
    {
        return response()->json(AppointmentResource::make($appointment->load('technicians')));
    }

    public function update(StoreAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment->update($request->validated());

        if ($request->has('technician_ids')) {
            $technicianIds = $this->resolveTechnicianIds($request->technician_ids ?? []);
            $appointment->technicians()->sync($technicianIds);
        }

        return response()->json(AppointmentResource::make($appointment->fresh('technicians')));
    }

    public function destroy(Appointment $appointment): JsonResponse
    {
        $appointment->delete();

        return response()->json(null, 204);
    }

    /**
     * Convert user IDs → technician IDs.
     * If the user already has a linked technician, use it.
     * Otherwise auto-create a technician record for them.
     */
    private function resolveTechnicianIds(array $userIds): array
    {
        $technicianIds = [];

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (! $user) continue;

            // Find existing technician linked to this user
            $technician = Technician::where('user_id', $userId)->first();

            if (! $technician) {
                // Auto-create a technician record for this user
                $technician = Technician::create([
                    'user_id'     => $userId,
                    'created_by'  => auth()->id(),
                    'full_name'   => $user->name,
                    'email'       => $user->email,
                    'phone_number'=> $user->phone_number,
                    'status'      => 'active',
                ]);
            }

            $technicianIds[] = $technician->id;
        }

        return $technicianIds;
    }
}
