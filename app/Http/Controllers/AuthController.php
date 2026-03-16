<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    // Hàm đăng ký users
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string|max:50|',
                'email' => 'required|email|max:100|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
                'avatar' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
            ]);

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'avatar' => $validated['avatar'] ?? null,
                'role' => 'user',
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Register successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Register failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Hàm đăng nhập
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            $user = User::query()->where('email', $validated['email'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Invalid email or password',
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Hàm lấy thông tin tài khoản đang đăng nhập
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (! $user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get current user successful',
                'data' => $user->loadCount(['posts', 'followers', 'following']),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get current user failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
