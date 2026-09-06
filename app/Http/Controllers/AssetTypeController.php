<?php

namespace App\Http\Controllers;

use App\Http\Resources\AssetTypeResource;
use App\Models\AssetType;
use App\Models\AssetTypeFailingRemark;
use App\Models\AssetTypeVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $types = AssetType::query()
            ->with(['variants', 'failingRemarks'])
            ->when($request->input("search"), fn ($q) => $q->where('name', 'like', "%{$request->input("search")}%"))
            ->orderBy('name')
            ->get();

        return response()->json(AssetTypeResource::collection($types));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'is_active'        => ['nullable', 'boolean'],
            'tags'             => ['nullable', 'array'],
            'category'         => ['nullable', 'string', 'max:255'],
            'sub_category'     => ['nullable', 'string', 'max:255'],
            'classification'    => ['nullable', 'string', 'max:255'],
            'default_frequency'   => ['nullable', 'array'],
            'default_frequency.*' => ['string', 'in:MONTHLY,QUARTERLY,SIX-MONTHLY,ANNUALLY,TWO-YEARLY,FIVE-YEARLY'],
            'default_fields'      => ['nullable', 'array'],
            'default_fields.*'    => ['string'],
            'variants'         => ['nullable', 'array'],
            'variants.*.name'  => ['required', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $type = AssetType::create([
            'name'              => $data['name'],
            'is_active'         => $data['is_active'] ?? true,
            'tags'              => $data['tags'] ?? [],
            'category'          => $data['category'] ?? null,
            'sub_category'      => $data['sub_category'] ?? null,
            'classification'    => $data['classification'] ?? null,
            'default_frequency' => $data['default_frequency'] ?? null,
            'default_fields'    => $data['default_fields'] ?? null,
            'created_by'        => auth()->id(),
        ]);

        if (! empty($data['variants'])) {
            $type->variants()->createMany($data['variants']);
        }

        return response()->json(AssetTypeResource::make($type->load(['variants', 'failingRemarks'])), 201);
    }

    public function show(AssetType $assetType): JsonResponse
    {
        return response()->json(AssetTypeResource::make($assetType->load(['variants', 'failingRemarks'])));
    }

    public function storeVariant(Request $request, AssetType $assetType): JsonResponse
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);
        $variant = $assetType->variants()->create([
            'name'  => $data['name'],
            'price' => $data['price'] ?? 0,
        ]);
        return response()->json([
            'id'    => $variant->id,
            'uid'   => (string) $variant->id,
            'name'  => $variant->name,
            'price' => $variant->price,
        ], 201);
    }

    public function storeFailingRemark(Request $request, AssetType $assetType): JsonResponse
    {
        $data = $request->validate([
            'remark'     => ['required', 'string'],
            'severity'   => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $remark = $assetType->failingRemarks()->create($data);
        return response()->json($remark, 201);
    }

    public function updateFailingRemark(Request $request, AssetType $assetType, AssetTypeFailingRemark $remark): JsonResponse
    {
        abort_if($remark->asset_type_id !== $assetType->id, 403);
        $data = $request->validate([
            'remark'     => ['nullable', 'string'],
            'severity'   => ['nullable', 'string'],
            'resolution' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $remark->update($data);
        return response()->json($remark);
    }

    public function destroyFailingRemark(AssetType $assetType, AssetTypeFailingRemark $remark): JsonResponse
    {
        abort_if($remark->asset_type_id !== $assetType->id, 403);
        $remark->delete();
        return response()->json(null, 204);
    }

    public function update(Request $request, AssetType $assetType): JsonResponse
    {
        $data = $request->validate([
            'name'           => ['nullable', 'string', 'max:255'],
            'is_active'      => ['nullable', 'boolean'],
            'tags'           => ['nullable', 'array'],
            'category'       => ['nullable', 'string', 'max:255'],
            'sub_category'   => ['nullable', 'string', 'max:255'],
            'classification'    => ['nullable', 'string', 'max:255'],
            'default_frequency'   => ['nullable', 'array'],
            'default_frequency.*' => ['string', 'in:MONTHLY,QUARTERLY,SIX-MONTHLY,ANNUALLY,TWO-YEARLY,FIVE-YEARLY'],
            'default_fields'      => ['nullable', 'array'],
            'default_fields.*'    => ['string'],
            'variants'       => ['nullable', 'array'],
            'variants.*.name'  => ['required_with:variants', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $assetType->update([
            'name'              => $data['name']              ?? $assetType->name,
            'is_active'         => $data['is_active']         ?? $assetType->is_active,
            'tags'              => $data['tags']              ?? $assetType->tags,
            'category'          => $data['category']          ?? $assetType->category,
            'sub_category'      => $data['sub_category']      ?? $assetType->sub_category,
            'classification'    => $data['classification']    ?? $assetType->classification,
            'default_frequency' => array_key_exists('default_frequency', $data) ? $data['default_frequency'] : $assetType->default_frequency,
            'default_fields'    => array_key_exists('default_fields', $data) ? $data['default_fields'] : $assetType->default_fields,
        ]);

        // Sync variants: delete existing, recreate from request
        if (array_key_exists('variants', $data)) {
            $assetType->variants()->delete();
            if (! empty($data['variants'])) {
                $assetType->variants()->createMany(
                    array_map(fn ($v) => ['name' => $v['name'], 'price' => $v['price'] ?? 0], $data['variants'])
                );
            }
        }

        return response()->json(AssetTypeResource::make($assetType->fresh(['variants', 'failingRemarks'])));
    }

    public function destroy(AssetType $assetType): JsonResponse
    {
        $assetType->delete();

        return response()->json(null, 204);
    }
}
