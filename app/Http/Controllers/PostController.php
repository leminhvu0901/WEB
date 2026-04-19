<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class PostController extends Controller
{
    // Lấy danh sách tất cả bài viết theo user
    public function byUser(int $user): JsonResponse
    {
        try {
            // Kiểm tra user có tồn tại hay không.
            $foundUser = User::query()->find($user);

            // Không có user -> trả JSON 404 để frontend xử lý.
            if (! $foundUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'User not found',
                ], 404);
            }

            // Lấy tất cả bài viết của user theo user_id,
            // kèm thông tin tác giả và danh sách ảnh.
            $posts = Post::query()
                ->where('user_id', $user)
                ->with(['user:id,username,email,avatar', 'images'])
                ->orderByDesc('id')
                ->get();

            // Trả về danh sách bài viết theo format JSON chuẩn của API.
            return response()->json([
                'status' => 'success',
                'message' => 'Get user posts successful',
                'data' => $posts,
            ]);
        } catch (Throwable $e) {
            // Bắt lỗi hệ thống và giữ response nhất quán.
            return response()->json([
                'status' => 'fail',
                'message' => 'Get user posts failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // Tạo bài viết mới (hỗ trợ nhiều hình ảnh)
    public function add(StorePostRequest $request): JsonResponse
    {
        try {
            // Lấy user đang đăng nhập từ token Sanctum.
            $authUser = $request->user();

            // Nếu chưa đăng nhập thì chặn thao tác tạo bài viết.
            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $validated = $request->validated();

            $uploadedImages = $request->file('images', []);
            $thumbnailIndex = $validated['thumbnail_index'] ?? 0;

            if ($thumbnailIndex >= count($uploadedImages)) {
                throw ValidationException::withMessages([
                    'thumbnail_index' => ['Thumbnail index is out of range.'],
                ]);
            }

            // Dùng transaction để đảm bảo tạo post và images thành công cùng nhau.
            $post = DB::transaction(function () use ($validated, $authUser, $uploadedImages, $thumbnailIndex) {
                // Tạo bản ghi bài viết gốc.
                $post = Post::query()->create([
                    'user_id' => $authUser->id,
                    'caption' => $validated['caption'] ?? null,
                    'status' => $validated['status'] ?? 'active',
                ]);

                $hasThumbnailColumn = Schema::hasColumn('post_images', 'is_thumbnail');

                // Chuẩn hóa mảng file ảnh về format createMany().
                $images = array_map(
                    static function (UploadedFile $imageFile, int $index) use ($thumbnailIndex, $hasThumbnailColumn): array {
                        $row = [
                            'image_url' => $imageFile->store('posts', 'public'),
                        ];

                        if ($hasThumbnailColumn) {
                            $row['is_thumbnail'] = $index === $thumbnailIndex;
                        }

                        return $row;
                    },
                    $uploadedImages,
                    array_keys($uploadedImages)
                );

                // Tạo toàn bộ ảnh liên kết với post.
                $post->images()->createMany($images);

                return $post;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Create post successful',
                // Trả về kèm user và images để client không cần gọi thêm API.
                'data' => $post->load(['user:id,username,email,avatar', 'images']),
            ], 201);
        } catch (ValidationException $e) {
            // Lỗi dữ liệu đầu vào.
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            // Lỗi hệ thống không mong muốn.
            return response()->json([
                'status' => 'fail',
                'message' => 'Create post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // Xem chi tiết 1 bài viết
    public function show(int $post): JsonResponse
    {
        try {
            // Lấy chi tiết post cùng thông tin user và images.
            $foundPost = Post::query()
                ->with(['user:id,username,email,avatar', 'images'])
                ->find($post);

            // Không tìm thấy post thì trả 404 JSON.
            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get post successful',
                'data' => $foundPost,
            ]);
        } catch (Throwable $e) {
            // Lỗi hệ thống.
            return response()->json([
                'status' => 'fail',
                'message' => 'Get post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // Xóa bài viết
    public function destroy(Request $request, int $post): JsonResponse
    {
        try {
            // User đăng nhập hiện tại.
            $authUser = $request->user();

            // Chưa đăng nhập -> 401.
            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Tìm post cần xóa.
            $foundPost = Post::query()->find($post);

            // Không có post -> 404.
            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Chỉ owner hoặc admin được phép xóa.
            $isOwner = $foundPost->user_id === $authUser->id;
            $isAdmin = $authUser->role === 'admin';

            if (! $isOwner && ! $isAdmin) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Forbidden',
                ], 403);
            }

            $imagePaths = $foundPost->images()
                ->pluck('image_url')
                ->filter() //bỏ giá trị rỗng
                ->all(); //thành mảng PHP

            // Xóa post, các bản ghi phụ thuộc sẽ đi theo theo rule của foreign key.
            $foundPost->delete();

            if (! empty($imagePaths)) {
                Storage::disk('public')->delete($imagePaths);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Delete post successful',
            ]);
        } catch (Throwable $e) {
            // Lỗi hệ thống.
            return response()->json([
                'status' => 'fail',
                'message' => 'Delete post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

}
