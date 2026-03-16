<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FollowController extends Controller
{
    // API follow user khác
    public function follow(Request $request, User $user): JsonResponse
    {
        try {
            // Lấy user đang đăng nhập từ token hiện tại.
            $authUser = $request->user();

            // Không có token/user thì từ chối request.
            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Chặn tự follow chính mình.
            if ($authUser->id === $user->id) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'You cannot follow yourself',
                ], 422);
            }

            // Kiểm tra quan hệ follow đã tồn tại hay chưa.
            $isFollowing = $authUser->following()
                ->where('users.id', $user->id)
                ->exists();

            // Nếu đã follow thì trả kết quả luôn, không tạo bản ghi trùng.
            if ($isFollowing) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already following this user',
                    'data' => [
                        'followed' => true,
                    ],
                ]);
            }

            // Tạo quan hệ follow mới, kèm created_at cho schema đang dùng datetime không default.
            $authUser->following()->attach($user->id, [
                'created_at' => now(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Follow successful',
                'data' => [
                    'followed' => true,
                ],
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'fail',
                'message' => 'Follow failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API bỏ follow user
    public function unfollow(Request $request, User $user): JsonResponse
    {
        try {
            // Lấy user đang đăng nhập từ token hiện tại.
            $authUser = $request->user();

            // Không có token/user thì từ chối request.
            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Chặn thao tác với chính tài khoản của mình cho rõ nghiệp vụ.
            if ($authUser->id === $user->id) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'You cannot unfollow yourself',
                ], 422);
            }

            // Xóa quan hệ follow; detach trả về số dòng bị xóa.
            $deletedCount = $authUser->following()->detach($user->id);

            return response()->json([
                'status' => 'success',
                'message' => $deletedCount > 0 ? 'Unfollow successful' : 'You are not following this user',
                'data' => [
                    'followed' => false,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Unfollow failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API lấy danh sách người đang follow user
    public function followers(User $user): JsonResponse
    {
        try {
            // Chỉ lấy các cột cần thiết cho danh sách.
            $followers = $user->followers()
                ->select('users.id', 'users.username', 'users.email', 'users.avatar')
                ->orderByDesc('follows.id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get followers successful',
                'data' => $followers,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get followers failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API lấy danh sách user mà tài khoản này đang follow
    public function following(User $user): JsonResponse
    {
        try {
            // Chỉ lấy các cột cần thiết cho danh sách.
            $following = $user->following()
                ->select('users.id', 'users.username', 'users.email', 'users.avatar')
                ->orderByDesc('follows.id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get following successful',
                'data' => $following,
            ]);
        } catch (Throwable $e) {
            // Bắt lỗi hệ thống để API luôn trả JSON nhất quán.
            return response()->json([
                'status' => 'fail',
                'message' => 'Get following failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
