<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contracts = Contract::query()
            ->with(['property', 'client', 'lines'])
            ->when($request->property_id, fn ($q) => $q->where('property_id', $request->property_id))
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($contracts);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id'          => ['required', 'integer', 'exists:properties,id'],
            'client_id'            => ['nullable', 'integer', 'exists:clients,id'],
            'name'                 => ['required', 'string', 'max:255'],
            'billing_type'         => ['nullable', Rule::in(['fixed', 'do_and_charge'])],
            'recurrence'           => ['nullable', 'string', 'max:100'],
            'contract_start_date'  => ['nullable', 'date'],
            'contract_finish_date' => ['nullable', 'date'],
            'first_invoice_date'   => ['nullable', 'date'],
            'review_date'          => ['nullable', 'date'],
            'status'               => ['nullable', Rule::in(['draft', 'active', 'expired', 'cancelled'])],
        ]);

        $contract = Contract::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json($contract->load('lines'), 201);
    }

    public function show(Contract $contract): JsonResponse
    {
        return response()->json($contract->load(['property', 'client', 'lines']));
    }

    public function update(Request $request, Contract $contract): JsonResponse
    {
        $contract->update($request->validate([
            'name'                 => ['nullable', 'string', 'max:255'],
            'billing_type'         => ['nullable', Rule::in(['fixed', 'do_and_charge'])],
            'recurrence'           => ['nullable', 'string'],
            'contract_start_date'  => ['nullable', 'date'],
            'contract_finish_date' => ['nullable', 'date'],
            'review_date'          => ['nullable', 'date'],
            'status'               => ['nullable', Rule::in(['draft', 'active', 'expired', 'cancelled'])],
        ]));

        return response()->json($contract->fresh('lines'));
    }

    public function destroy(Contract $contract): JsonResponse
    {
        $contract->delete();

        return response()->json(null, 204);
    }
}
