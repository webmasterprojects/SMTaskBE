<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'slug'       => ['required', 'string', 'max:100', 'unique:roles,slug'],
            'permissions'=> ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        // Only one default role at a time
        if (!empty($data['is_default'])) {
            Role::where('is_default', true)->update(['is_default' => false]);
        }

        $role = Role::create($data);

        return response()->json($role, 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:255'],
            'slug'       => ['nullable', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($role->id)],
            'permissions'=> ['nullable', 'array'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if (!empty($data['is_default'])) {
            Role::where('is_default', true)->where('id', '!=', $role->id)->update(['is_default' => false]);
        }

        $role->update($data);

        return response()->json($role->fresh());
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete a role that has users assigned to it.'], 422);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(config('permissions'));
    }
}
