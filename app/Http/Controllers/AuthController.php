<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'avatar' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'avatar' => $validated['avatar'] ?? null,
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'role' => 'user',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Register successful',
            'token' => $token,
            'data' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $field = filter_var($validated['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::where($field, $validated['login'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Invalid credentials.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'data' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadCount(['posts', 'followers', 'following']);

        return response()->json([
            'data' => $user,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'username' => 'sometimes|string|max:50|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|max:100|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'avatar' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),
        ]);
    }
}
