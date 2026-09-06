<?php

namespace App\Http\Controllers;

use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Resources\AssetResource;
use App\Http\Resources\PropertyResource;
use App\Http\Resources\RoutineResource;
use App\Http\Resources\TaskResource;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = Property::query()
            ->with('client')
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->input("search"), fn ($q) => $q->where('name', 'like', "%{$request->input("search")}%"))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json(PropertyResource::collection($properties)->response()->getData(true));
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = Property::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return response()->json(PropertyResource::make($property->load('client')), 201);
    }

    public function show(Property $property): JsonResponse
    {
        return response()->json(PropertyResource::make($property->load('client')));
    }

    public function update(StorePropertyRequest $request, Property $property): JsonResponse
    {
        $property->update($request->validated());

        return response()->json(PropertyResource::make($property->fresh('client')));
    }

    public function patchNote(Request $request, Property $property): JsonResponse
    {
        $data = $request->validate([
            'property_note' => ['nullable', 'string', 'max:5000'],
        ]);
        $property->update($data);
        return response()->json(PropertyResource::make($property->fresh('client')));
    }

    public function destroy(Property $property): JsonResponse
    {
        $property->delete();

        return response()->json(null, 204);
    }

    public function assets(Property $property, Request $request): JsonResponse
    {
        $assets = $property->assets()
            ->with(['assetType', 'assetTypeVariant'])
            ->when($request->input("search"), fn ($q) => $q->where('label', 'like', "%{$request->input("search")}%"))
            ->paginate($request->per_page ?? 20);

        return response()->json(AssetResource::collection($assets)->response()->getData(true));
    }

    public function routines(Property $property, Request $request): JsonResponse
    {
        $routines = $property->routines()
            ->with(['asset'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($request->per_page ?? 20);

        return response()->json(RoutineResource::collection($routines)->response()->getData(true));
    }

    public function tasks(Property $property, Request $request): JsonResponse
    {
        $tasks = $property->tasks()
            ->with(['routines.asset', 'assets', 'appointments'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->paginate($request->per_page ?? 20);

        return response()->json(TaskResource::collection($tasks)->response()->getData(true));
    }
}

