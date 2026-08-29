<?php

namespace App\Http\Controllers;

use App\Http\Requests\Routine\StoreRoutineRequest;
use App\Http\Resources\RoutineResource;
use App\Models\Routine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoutineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $routines = Routine::query()
            ->with(['asset', 'property'])
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->asset_id, fn ($q) => $q->where('asset_id', $request->asset_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json(RoutineResource::collection($routines)->response()->getData(true));
    }

    public function store(StoreRoutineRequest $request): JsonResponse
    {
        $routine = Routine::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return response()->json(RoutineResource::make($routine->load('asset', 'property')), 201);
    }

    public function show(Routine $routine): JsonResponse
    {
        return response()->json(RoutineResource::make($routine->load('asset', 'property')));
    }

    public function update(StoreRoutineRequest $request, Routine $routine): JsonResponse
    {
        $routine->update($request->validated());

        return response()->json(RoutineResource::make($routine->fresh('asset', 'property')));
    }

    public function destroy(Routine $routine): JsonResponse
    {
        $routine->delete();

        return response()->json(null, 204);
    }

    public function bulkCreate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'routines'               => ['required', 'array', 'min:1'],
            'routines.*.property_id' => ['required', 'integer', 'exists:properties,id'],
            'routines.*.asset_id'    => ['required', 'integer', 'exists:assets,id'],
            'routines.*.frequency'   => ['required', 'array'],
            'routines.*.annual_date' => ['required', 'date'],
            'routines.*.start_date'  => ['required', 'date'],
        ]);

        $created = DB::transaction(function () use ($data) {
            return collect($data['routines'])->map(fn ($r) => Routine::create([
                ...$r,
                'created_by' => auth()->id(),
            ]));
        });

        return response()->json(['created' => $created->count()], 201);
    }

    public function due(Request $request): JsonResponse
    {
        $days = (int) $request->get('days', 7);

        $keys = cache()->getMultiple(
            collect(range(0, 100))->map(fn ($i) => "due-routine:*")->toArray()
        );

        return response()->json(['message' => 'Use artisan routines:check-due to compute due routines.']);
    }
}
