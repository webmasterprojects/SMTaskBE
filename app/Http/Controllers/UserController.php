<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->input("search"), fn ($q) => $q->where('name', 'like', "%{$request->input("search")}%")
                ->orWhere('email', 'like', "%{$request->input("search")}%"))
            ->when($request->role, fn ($q) => $q->where('role', $request->role))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('name')
            ->paginate($request->per_page ?? 20);

        return response()->json(UserResource::collection($users)->response()->getData(true));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8'],
            'role'         => ['nullable', Rule::in(['admin', 'user'])],
            'role_id'      => ['nullable', 'integer', 'exists:roles,id'],
            'status'       => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'permissions'  => ['nullable', 'array'],
        ]);

        $user = User::create($data);

        return response()->json(UserResource::make($user->load('linkedRole')), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(UserResource::make($user->load('technician', 'linkedRole')));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'         => ['nullable', 'string', 'max:255'],
            'email'        => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role'         => ['nullable', Rule::in(['admin', 'user'])],
            'role_id'      => ['nullable', 'integer', 'exists:roles,id'],
            'status'       => ['nullable', Rule::in(['active', 'inactive', 'suspended'])],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'permissions'  => ['nullable', 'array'],
        ]);

        $user->update($data);

        return response()->json(UserResource::make($user->fresh()->load('linkedRole')));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(null, 204);
    }
}

