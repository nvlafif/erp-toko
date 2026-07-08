<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('username', $credentials['username'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password tidak sesuai.'],
            ]);
        }

        if (! $user->is_active || ! $user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Akun belum aktif atau belum disetujui.',
                'errors' => [
                    'username' => ['Akun belum aktif atau belum disetujui.'],
                ],
            ], 403);
        }

        $token = $user->createToken(
            name: $request->input('device_name', 'pos-device'),
            abilities: [$user->role],
        )->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function register(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new UserResource($user),
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => new UserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
        ]);
    }

    public function updateUserStatus(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
            'is_approved' => ['required', 'boolean'],
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => new UserResource($user->fresh()),
        ]);
    }
}
