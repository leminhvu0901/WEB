<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserController extends Controller
{
    // get data danh sách tất cả users
    public function index(): JsonResponse
    {
        try {
            $users = User::query()
                ->withCount(['posts', 'followers', 'following'])
                ->orderByDesc('id')
                ->limit(20)
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get users successful',
                'data' => $users,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get users failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // get data 1 users


    // thêm users - bỏ hàm này vì đã có hàm register
    // public function store(Request $request): JsonResponse
    // {
    //     try {
    //         $validated = $request->validate([
    //             'username' => 'required|string|max:50|',
    //             'email' => 'required|email|max:100|unique:users,email',
    //             'password' => 'required|string|min:6|confirmed',
    //             'avatar' => 'nullable|string|max:255',
    //             'phone' => 'nullable|string|max:20',
    //             'address' => 'nullable|string|max:255',
    //         ]);

    //         $user = User::create([
    //             'username' => $validated['username'],
    //             'email' => $validated['email'],
    //             'password' => $validated['password'],
    //             'avatar' => $validated['avatar'] ?? null,
    //             'role' => 'user',
    //             'phone' => $validated['phone'] ?? null,
    //             'address' => $validated['address'] ?? null,
    //         ]);

    //         return response()->json([
    //             'status' => 'success',
    //             'message' => 'User created successfully',
    //             'data' => $user,
    //         ], 201);
    //     } catch (ValidationException $e) { //bắt các lỗi riêng trong  validated
    //         return response()->json([
    //             'status' => 'fail',
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (Throwable $e) { // bắt các lỗi còn lại không có trong validated
    //         return response()->json([
    //             'status' => 'fail',
    //             'message' => 'Create user failed',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

      public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Get user successful',
            'data' => $user->loadCount(['posts', 'followers', 'following']), ///lay do bai dang va luoi theo doi o bang khac
        ]);
    }

    // cập nhật
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'username' => 'sometimes|string|max:50|',//sotime là không bị bắt lỗi khi bạn không sửa hết mà chỉ sửa 1 vài cái
                'email' => 'sometimes|email|max:100|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
                'avatar' => 'nullable|string|max:255',
                'role' => 'sometimes|in:admin,user',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:255',
            ]);

            if (empty($validated['password'])) {// kiểm tra nếu pass rỗng thì xóa luôn null , chánh bị pass rỗng
                unset($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->fresh(),
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
                'message' => 'Update user failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(User $user): JsonResponse
    {
        try {
            $user->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Delete user failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
