<?php

namespace App\Http\Controllers;

use App\Http\Requests\Asset\StoreAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Models\AssetWorkHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $assets = Asset::query()
            ->with(['assetType', 'assetTypeVariant'])
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->asset_type_id, fn ($q) => $q->where('asset_type_id', $request->asset_type_id))
            ->when($request->input("search"), fn ($q) => $q->where('label', 'like', "%{$request->input("search")}%"))
            ->orderBy('label')
            ->paginate($request->per_page ?? 20);

        return response()->json(AssetResource::collection($assets)->response()->getData(true));
    }

    public function store(StoreAssetRequest $request): JsonResponse
    {
        $asset = Asset::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return response()->json(AssetResource::make($asset->load('assetType', 'assetTypeVariant')), 201);
    }

    public function show(Asset $asset): JsonResponse
    {
        return response()->json(AssetResource::make($asset->load('assetType', 'assetTypeVariant')));
    }

    public function update(StoreAssetRequest $request, Asset $asset): JsonResponse
    {
        $asset->update($request->validated());

        return response()->json(AssetResource::make($asset->fresh('assetType', 'assetTypeVariant')));
    }

    public function destroy(Asset $asset): JsonResponse
    {
        $asset->delete();

        return response()->json(null, 204);
    }

    public function addWorkHistory(Request $request, Asset $asset): JsonResponse
    {
        $data = $request->validate([
            'status'     => ['required', 'in:pass,fail,remark'],
            'remarks'    => ['nullable', 'string', 'max:2000'],
            'severity'   => ['nullable', 'string', 'max:100'],
            'resolution' => ['nullable', 'string', 'max:2000'],
            'images'     => ['nullable', 'array'],
            'task_id'    => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $history = AssetWorkHistory::create([
            ...$data,
            'asset_id'    => $asset->id,
            'recorded_by' => auth()->id(),
        ]);

        // Return the full updated work history so frontend can refresh
        $allHistory = $asset->workHistory()->with(['recordedBy', 'task'])->orderBy('recorded_at')->get();
        return response()->json([
            'record'      => $history,
            'workHistory' => $allHistory->map(fn ($h) => [
                ...$h->toArray(),
                'recorded_by_name' => $h->recordedBy?->full_name ?? $h->recordedBy?->name ?? null,
                'task_reference'   => $h->task ? ($h->task->reference ?? $h->task->id) : null,
                'task_id'          => $h->task_id,
            ]),
        ], 201);
    }

    public function getWorkHistory(Asset $asset): JsonResponse
    {
        $history = $asset->workHistory()->with(['recordedBy', 'task'])->orderBy('recorded_at')->get();
        return response()->json($history->map(fn ($h) => [
            ...$h->toArray(),
            'recorded_by_name' => $h->recordedBy?->full_name ?? $h->recordedBy?->name ?? null,
            'task_reference'   => $h->task ? ($h->task->reference ?? $h->task->id) : null,
            'task_id'          => $h->task_id,
        ]));
    }
}

