<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyContactController extends Controller
{
    public function index(Property $property): JsonResponse
    {
        $contacts = $property->contacts()->with('assetType')->orderBy('id')->get();
        return response()->json($contacts->map(fn ($c) => $this->format($c)));
    }

    public function store(Request $request, Property $property): JsonResponse
    {
        $data = $request->validate([
            'asset_type_id' => ['nullable', 'integer', 'exists:asset_types,id'],
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:255'],
            'notes'         => ['nullable', 'string'],
        ]);

        $contact = $property->contacts()->create($data);
        return response()->json($this->format($contact->load('assetType')), 201);
    }

    public function update(Request $request, Property $property, PropertyContact $contact): JsonResponse
    {
        abort_if($contact->property_id !== $property->id, 403);
        $data = $request->validate([
            'asset_type_id' => ['nullable', 'integer', 'exists:asset_types,id'],
            'name'          => ['sometimes', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'email'         => ['nullable', 'email', 'max:255'],
            'notes'         => ['nullable', 'string'],
        ]);

        $contact->update($data);
        return response()->json($this->format($contact->load('assetType')));
    }

    public function destroy(Property $property, PropertyContact $contact): JsonResponse
    {
        abort_if($contact->property_id !== $property->id, 403);
        $contact->delete();
        return response()->json(null, 204);
    }

    private function format(PropertyContact $c): array
    {
        return [
            'id'              => $c->id,
            'asset_type_id'   => $c->asset_type_id,
            'asset_type_name' => $c->assetType?->name,
            'name'            => $c->name,
            'phone'           => $c->phone,
            'email'           => $c->email,
            'notes'           => $c->notes,
        ];
    }
}
