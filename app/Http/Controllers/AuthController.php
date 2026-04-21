<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\StoresOriginalFileNames;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    use StoresOriginalFileNames;

    // Hàm đăng ký user
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'avatar' => null,
                'role' => 'user',
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
            ]);

            if ($request->hasFile('avatar')) {
                $avatarPath = $this->storePublicFileWithOriginalName(
                    $request->file('avatar'),
                    'uploads/avatars/user-'.$user->id
                );

                $user->forceFill(['avatar' => $avatarPath])->save();
                $user->refresh();
            }

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
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            //tim va lay user
            $user = User::query()->where('email', $validated['email'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) { //kiem tra user và password
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
            $user = $request->user(); // tra ve user hien tai

            if (! $user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get current user successful',
                'data' => $user->loadCount(['posts']),
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
