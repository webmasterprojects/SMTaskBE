<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to   = $request->input('to',   now()->endOfMonth()->toDateString());

        $isAdmin  = auth()->user()->role === 'admin';
        $mineOnly = $request->boolean('mine');

        $myTechnician = (!$isAdmin || $mineOnly)
            ? Technician::where('user_id', auth()->id())->whereNull('deleted_at')->first()
            : null;

        $appointments = Appointment::with(['technicians', 'task.property'])
            ->whereNull('deleted_at')
            ->where('start_date_time', '>=', $from . ' 00:00:00')
            ->where('start_date_time', '<=', $to   . ' 23:59:59')
            ->when($myTechnician, fn ($q) =>
                $q->whereHas('technicians', fn ($q2) => $q2->where('technicians.id', $myTechnician->id))
            )
            ->orderBy('start_date_time')
            ->get();

        $technicianQuery = Technician::whereNull('deleted_at')
            ->where('status', 'active')
            ->orderBy('full_name');

        if ($myTechnician) {
            $technicianQuery->where('id', $myTechnician->id);
        }

        $technicians = $technicianQuery->get()->map(fn ($t) => [
            'id'        => $t->id,
            'user_id'   => $t->user_id,
            'full_name' => $t->full_name,
            'email'     => $t->email,
        ]);

        $mapped = $appointments->map(fn ($a) => [
            'id'            => $a->id,
            'task_id'       => $a->task_id,
            'summary'       => $a->summary,
            'start'         => $a->start_date_time,
            'end'           => $a->end_date_time,
            'task_status'   => $a->task?->status,
            'property_name' => $a->task?->property?->name,
            'address'       => $a->task?->property?->formatted_address,
            'technician_ids'=> $a->technicians->pluck('id')->values()->toArray(),
        ]);

        return response()->json([
            'technicians'  => $technicians->values(),
            'appointments' => $mapped->values(),
        ]);
    }
}
