<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Support\StoresOriginalFileNames;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UserController extends Controller
{
    use StoresOriginalFileNames;

    // get data danh sách tất cả users
    public function index(): JsonResponse
    {
        try {
            $users = User::query()
                ->withCount(['posts'])//dem bai post
                ->orderByDesc('id') //moi nhat them trc
                ->limit(20)
                ->get(); //lay du lieu

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



    //lay 1 user
    public function show(int $user): JsonResponse
    {
        try {
            $foundUser = User::query()
                ->withCount(['posts'])
                ->find($user);

            if (!$foundUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get user successful',
                'data' => $foundUser,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get user failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // cập nhật
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validated();

            if (empty($validated['password'])) {// kiểm tra nếu pass rỗng thì xóa luôn null , chánh bị pass rỗng
                unset($validated['password']);
            }

            if ($request->hasFile('avatar')) {
                if (!empty($user->avatar) && Storage::disk('public')->exists($user->avatar)) {
                    Storage::disk('public')->delete($user->avatar);
                }

                $validated['avatar'] = $this->storePublicFileWithOriginalName(
                    $request->file('avatar'),
                    'uploads/avatars/user-'.$user->id
                );
            } else {
                unset($validated['avatar']);
            }

            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully',
                'data' => $user->fresh(),
            ]);
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
