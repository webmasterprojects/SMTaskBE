<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BillingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $billings = Billing::query()
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->orderBy('name')
            ->paginate($request->per_page ?? 15);

        return response()->json($billings);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'client_id'               => ['required', 'integer', 'exists:clients,id'],
            'property_id'             => ['nullable', 'integer', 'exists:properties,id'],
            'name'                    => ['required', 'string', 'max:255'],
            'reference'               => ['nullable', 'string', 'max:100'],
            'abn_number'              => ['nullable', 'string', 'max:20'],
            'accounting_organisation' => ['nullable', 'string', 'max:255'],
            'attention'               => ['nullable', 'string', 'max:255'],
            'email'                   => ['nullable', 'email', 'max:255'],
            'phone_number'            => ['nullable', 'string', 'max:50'],
            'bh_phone_number'         => ['nullable', 'string', 'max:50'],
            'ah_phone_number'         => ['nullable', 'string', 'max:50'],
            'fax_number'              => ['nullable', 'string', 'max:50'],
            'postal_address'          => ['nullable', 'string', 'max:1000'],
            'status'                  => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $billing = Billing::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json($billing, 201);
    }

    public function show(Billing $billing): JsonResponse
    {
        return response()->json($billing);
    }

    public function update(Request $request, Billing $billing): JsonResponse
    {
        $billing->update($request->only([
            'name', 'reference', 'abn_number', 'attention', 'email',
            'phone_number', 'postal_address', 'status',
        ]));

        return response()->json($billing->fresh());
    }

    public function destroy(Billing $billing): JsonResponse
    {
        $billing->delete();

        return response()->json(null, 204);
    }
}
