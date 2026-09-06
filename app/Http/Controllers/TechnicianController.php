<?php

namespace App\Http\Controllers;

use App\Http\Resources\TechnicianResource;
use App\Models\Technician;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TechnicianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Technician::query();

        // If user has technician.self but NOT technician.view, restrict to own record only
        if ($user->hasPermission('technician.self') && !$user->hasPermission('technician.view')) {
            $query->where('user_id', $user->id);
        }

        $technicians = $query
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->input("search"), fn ($q) => $q->where('full_name', 'like', "%{$request->input("search")}%"))
            ->orderBy('full_name')
            ->paginate($request->per_page ?? 20);

        return response()->json(TechnicianResource::collection($technicians)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'      => ['nullable', 'integer', 'exists:users,id'],
            'full_name'    => ['required', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'branch'       => ['nullable', 'string', 'max:100'],
            'zone'         => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $technician = Technician::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return response()->json(TechnicianResource::make($technician), 201);
    }

    public function show(Technician $technician): JsonResponse
    {
        return response()->json(TechnicianResource::make($technician));
    }

    public function update(Request $request, Technician $technician): JsonResponse
    {
        $data = $request->validate([
            'full_name'    => ['nullable', 'string', 'max:255'],
            'email'        => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'address'      => ['nullable', 'string', 'max:1000'],
            'branch'       => ['nullable', 'string', 'max:100'],
            'zone'         => ['nullable', 'string', 'max:100'],
            'status'       => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $technician->update($data);

        return response()->json(TechnicianResource::make($technician->fresh()));
    }

    public function destroy(Technician $technician): JsonResponse
    {
        $technician->delete();

        return response()->json(null, 204);
    }
}

