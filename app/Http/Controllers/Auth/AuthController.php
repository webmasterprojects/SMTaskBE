<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return response()->json(['message' => 'Account is not active.'], 403);
        }

        $deviceName = $request->input('device_name', 'api-token');

        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName);

        return response()->json([
            'user'  => UserResource::make($user),
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Revoke Bearer token if the request was token-authenticated
        $token = $request->user()?->currentAccessToken();
        if ($token && ! ($token instanceof \Laravel\Sanctum\TransientToken)) {
            $token->delete();
        }

        // Destroy web session (cookie-based SPA auth)
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(UserResource::make($request->user()->load('technician', 'linkedRole')));
    }

    public function getPreferences(Request $request): JsonResponse
    {
        return response()->json($request->user()->preferences ?? (object)[]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'work_tab_fields'              => ['nullable', 'array'],
            'work_tab_fields.*'            => ['array'],
            'work_tab_fields.*.*'          => ['string'],
        ]);

        $user = $request->user();
        $current = $user->preferences ?? [];
        $user->update(['preferences' => array_merge($current, $data)]);

        return response()->json($user->preferences);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password'     => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! \Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => \Illuminate\Support\Facades\Hash::make($data['new_password'])]);

        // Revoke all other tokens so existing sessions are invalidated
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('api-token');

        return response()->json(['token' => $token->plainTextToken]);
    }
}
