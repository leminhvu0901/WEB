<?php

namespace App\Http\Controllers;

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
    public function add(Request $request): JsonResponse
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

            // Validate input: caption trạng thái là tùy chọn, nhưng images bắt buộc phải có ít nhất 1 file ảnh.
            $validated = $request->validate([
                'caption' => 'nullable|string',
                'status' => 'sometimes|in:active,hidden',
                'images' => 'required|array|min:1',
                'images.*' => 'required|file|image|max:5120',
                'thumbnail_index' => 'nullable|integer|min:0',
            ]);

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

    // Cập nhật bài viết và danh sách hình ảnh
    public function update(Request $request, int $post): JsonResponse
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

            // Tìm post cần cập nhật.
            $foundPost = Post::query()->with('images')->find($post);

            // Không có post -> 404.
            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Chỉ owner hoặc admin mới có quyền sửa.
            $isOwner = $foundPost->user_id === $authUser->id;
            $isAdmin = $authUser->role === 'admin';

            if (! $isOwner && ! $isAdmin) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Forbidden',
                ], 403);
            }

            // Validate dữ liệu cập nhật. Tất cả đều là optional (partial update).
            $validated = $request->validate([
                'caption' => 'sometimes|nullable|string',
                'status' => 'sometimes|in:active,hidden',
                'images' => 'sometimes|array|min:1',
                'images.*' => 'required_with:images|file|image|max:5120',
                'add_images' => 'sometimes|array|min:1',
                'add_images.*' => 'required_with:add_images|file|image|max:5120',
                'delete_image_ids' => 'sometimes|array|min:1',
                'delete_image_ids.*' => 'required_with:delete_image_ids|integer|distinct',
                'thumbnail_index' => 'nullable|integer|min:0',
            ]);

            $replacementImages = $request->file('images', []);
            $additionalImages = $request->file('add_images', []);

            // Không cho phép vừa thay toàn bộ images, vừa add/delete trong cùng 1 request.
            if (
                array_key_exists('images', $validated)
                && (array_key_exists('add_images', $validated) || array_key_exists('delete_image_ids', $validated))
            ) {
                throw ValidationException::withMessages([
                    'images' => ['Cannot combine full image replacement with add/delete image operations.'],
                ]);
            }

            // Cập nhật post + images trong 1 transaction để tránh dữ liệu nửa chừng.
            DB::transaction(function () use ($validated, $foundPost, $replacementImages, $additionalImages) {
                $postData = [];
                $hasThumbnailColumn = Schema::hasColumn('post_images', 'is_thumbnail');

                // Chỉ cập nhật caption nếu client có truyền key này.
                if (array_key_exists('caption', $validated)) {
                    $postData['caption'] = $validated['caption'];
                }

                // Chỉ cập nhật status nếu client có truyền key này.
                if (array_key_exists('status', $validated)) {
                    $postData['status'] = $validated['status'];
                }

                // Có dữ liệu thay đổi mới update bản ghi post.
                if (! empty($postData)) {
                    $foundPost->update($postData);
                }

                // Nếu client gửi lại images, thay thế toàn bộ danh sách ảnh cũ.
                if (array_key_exists('images', $validated)) {
                    $thumbnailIndex = $validated['thumbnail_index'] ?? 0;

                    if ($thumbnailIndex >= count($replacementImages)) {
                        throw ValidationException::withMessages([
                            'thumbnail_index' => ['Thumbnail index is out of range.'],
                        ]);
                    }

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
                        $replacementImages,
                        array_keys($replacementImages)
                    );

                    $oldImagePaths = $foundPost->images()
                        ->pluck('image_url')
                        ->filter()
                        ->all();

                    // Xóa ảnh cũ rồi thêm ảnh mới.
                    $foundPost->images()->delete();
                    $foundPost->images()->createMany($images);

                    if (! empty($oldImagePaths)) {
                        Storage::disk('public')->delete($oldImagePaths);
                    }
                } else {
                    // Hỗ trợ update linh hoạt: thêm ảnh mới hoặc xóa theo image id.
                    if (array_key_exists('delete_image_ids', $validated)) {
                        $deleteImageIds = $validated['delete_image_ids'];

                        $imagesToDelete = $foundPost->images()
                            ->whereIn('id', $deleteImageIds)
                            ->get(['id', 'image_url']);

                        $ownedImageIds = $imagesToDelete
                            ->pluck('id')
                            ->all();

                        if (count($ownedImageIds) !== count($deleteImageIds)) {
                            throw ValidationException::withMessages([
                                'delete_image_ids' => ['Some images do not belong to this post.'],
                            ]);
                        }

                        $foundPost->images()->whereIn('id', $deleteImageIds)->delete();

                        $imagePathsToDelete = $imagesToDelete
                            ->pluck('image_url')
                            ->filter()
                            ->all();

                        if (! empty($imagePathsToDelete)) {
                            Storage::disk('public')->delete($imagePathsToDelete);
                        }
                    }

                    if (array_key_exists('add_images', $validated)) {
                        $newImages = array_map(
                            static function (UploadedFile $imageFile) use ($hasThumbnailColumn): array {
                                $row = [
                                    'image_url' => $imageFile->store('posts', 'public'),
                                ];

                                if ($hasThumbnailColumn) {
                                    $row['is_thumbnail'] = false;
                                }

                                return $row;
                            },
                            $additionalImages
                        );

                        $foundPost->images()->createMany($newImages);
                    }
                }

                // Giữ rule nhất quán với API tạo mới: bài viết phải còn ít nhất 1 ảnh.
                if ($foundPost->images()->count() === 0) {
                    throw ValidationException::withMessages([
                        'images' => ['A post must have at least one image.'],
                    ]);
                }

                if ($hasThumbnailColumn) {
                    // Chuẩn hóa thumbnail: luôn có đúng 1 thumbnail sau khi update.
                    $thumbnailIds = $foundPost->images()
                        ->where('is_thumbnail', true)
                        ->orderBy('id')
                        ->pluck('id')
                        ->all();

                    if (empty($thumbnailIds)) {
                        $firstImageId = $foundPost->images()->orderBy('id')->value('id');

                        if ($firstImageId) {
                            $foundPost->images()->where('id', $firstImageId)->update(['is_thumbnail' => true]);
                        }
                    } elseif (count($thumbnailIds) > 1) {
                        $keepThumbnailId = $thumbnailIds[0];

                        $foundPost->images()
                            ->where('id', '!=', $keepThumbnailId)
                            ->update(['is_thumbnail' => false]);
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Update post successful',
                // fresh() để lấy dữ liệu mới nhất sau transaction.
                'data' => $foundPost->fresh()->load(['user:id,username,email,avatar', 'images']),
            ]);
        } catch (ValidationException $e) {
            // Lỗi validate.
            return response()->json([
                'status' => 'fail',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Throwable $e) {
            // Lỗi hệ thống.
            return response()->json([
                'status' => 'fail',
                'message' => 'Update post failed',
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
                ->filter()
                ->all();

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
