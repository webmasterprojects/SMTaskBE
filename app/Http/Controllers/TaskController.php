<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\CreateOnDemandTaskRequest;
use App\Http\Requests\Task\CreateRoutineTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Asset;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = Task::query()
            ->with(['property', 'routines.asset', 'assets', 'appointments.technicians', 'serviceCategory'])
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->type, fn ($q) => $q->where('type', $request->type))
            ->when($request->status, fn ($q) => $q->whereIn('status', array_filter(explode(',', $request->status))))
            ->when($request->technician_id, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('created_by', $request->technician_id)
                ->orWhereHas('appointments.technicians', fn ($q3) => $q3->where('users.id', $request->technician_id))
            ))
            ->when($request->input("search"), fn ($q) => $q->where('label', 'like', "%{$request->input("search")}%"))
            ->orderBy($request->sort_by ?? 'created_at', $request->sort_dir ?? 'desc')
            ->paginate($request->per_page ?? $request->limit ?? 15);

        return response()->json(TaskResource::collection($tasks)->response()->getData(true));
    }

    public function store(CreateRoutineTaskRequest $request): JsonResponse
    {
        $task = DB::transaction(function () use ($request) {
            $task = Task::create([
                'property_id'         => $request->property_id,
                'created_by'          => auth()->id(),
                'type'                => 'routine',
                'label'               => $request->label,
                'technician_note'     => $request->technician_note,
                'invoice_note'        => $request->invoice_note,
                'internal_note'       => $request->internal_note,
                'service_category_id' => $request->service_category_id,
                'status'              => 'ready',
            ]);

            if (!empty($request->routine_ids)) {
                $task->routines()->attach($request->routine_ids);
            }

            return $task;
        });

        return response()->json(TaskResource::make($task->load('routines.asset', 'property')), 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json(TaskResource::make(
            $task->load(['property', 'routines.asset', 'assets', 'appointments.technicians', 'serviceCategory'])
        ));
    }

    public function createOnDemand(CreateOnDemandTaskRequest $request): JsonResponse
    {
        $task = DB::transaction(function () use ($request) {
            $task = Task::create([
                'property_id'     => $request->property_id,
                'created_by'      => auth()->id(),
                'type'            => 'on_demand',
                'label'           => $request->label,
                'technician_note' => $request->technician_note,
                'status'          => 'ready',
            ]);

            $task->assets()->attach($request->asset_ids);

            return $task;
        });

        return response()->json(TaskResource::make($task->load('assets', 'property')), 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();

        // Technicians cannot set internal_note
        if (auth()->user()->role === 'technician') {
            unset($data['internal_note']);
        }

        $task->update($data);

        if ($request->has('routine_ids')) {
            $task->routines()->sync($request->routine_ids);
        }

        if ($request->has('asset_ids')) {
            $task->assets()->sync($request->asset_ids);
        }

        return response()->json(TaskResource::make($task->fresh(['routines', 'assets', 'appointments', 'serviceCategory'])));
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(null, 204);
    }

    // GET /tasks/{task}/asset-results
    // Returns all property assets merged with this task's per-asset results
    public function assetResults(Task $task): JsonResponse
    {
        $task->load('property.assets');

        // Map task-specific results keyed by asset_id
        $results = DB::table('task_assets')
            ->where('task_id', $task->id)
            ->get()
            ->keyBy('asset_id');

        $assets = ($task->property?->assets ?? collect())->map(function ($asset) use ($results) {
            $pivot = $results->get($asset->id);
            return [
                'id'       => $asset->id,
                'uid'      => (string) $asset->id,
                'label'    => $asset->label,
                'type'     => $asset->type,
                'size'     => $asset->size,
                'location' => $asset->location,
                'notes'    => $asset->notes,
                'status'   => $pivot?->status ?? null,
                'remarks'  => $pivot?->remarks ?? null,
                'tested_at'=> $pivot?->tested_at ?? null,
            ];
        });

        $total     = $assets->count();
        $completed = $assets->whereNotNull('status')->count();

        return response()->json([
            'assets'    => $assets->values(),
            'total'     => $total,
            'completed' => $completed,
        ]);
    }

    // GET /tasks/{task}/safety-acks
    public function safetyAcks(Task $task): JsonResponse
    {
        $acks = DB::table('task_safety_acknowledgements')
            ->where('task_id', $task->id)
            ->join('users', 'users.id', '=', 'task_safety_acknowledgements.user_id')
            ->select(
                'task_safety_acknowledgements.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->orderByDesc('task_safety_acknowledgements.submitted_at')
            ->get();

        return response()->json($acks);
    }

    // POST /tasks/{task}/safety-acks
    public function storeSafetyAck(Request $request, Task $task): JsonResponse
    {
        $data = $request->validate([
            'employee_info' => ['nullable', 'array'],
            'answers'       => ['nullable', 'array'],
            'submitted_at'  => ['nullable', 'date'],
        ]);

        $submittedAt = isset($data['submitted_at'])
            ? \Carbon\Carbon::parse($data['submitted_at'])->setTimezone(config('app.timezone'))
            : now();

        DB::table('task_safety_acknowledgements')->updateOrInsert(
            ['task_id' => $task->id, 'user_id' => auth()->id()],
            [
                'employee_info' => json_encode($data['employee_info'] ?? null),
                'answers'       => json_encode($data['answers'] ?? null),
                'submitted_at'  => $submittedAt,
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        return response()->json(['message' => 'Acknowledged']);
    }

    // PATCH /tasks/{task}/asset-results/{asset}
    public function updateAssetResult(Request $request, Task $task, Asset $asset): JsonResponse
    {
        $data = $request->validate([
            'status'  => ['required', Rule::in(['PASS', 'FAIL', 'NO_TEST'])],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::table('task_assets')->updateOrInsert(
            ['task_id' => $task->id, 'asset_id' => $asset->id],
            [
                'status'     => $data['status'],
                'remarks'    => $data['remarks'] ?? null,
                'tested_at'  => now(),
                'tested_by'  => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['message' => 'Asset result saved']);
    }
}

