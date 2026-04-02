<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostLike;
use App\Models\PostShare;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class PostController extends Controller
{
    // API lấy danh sách user đã like bài viết
    public function likes(int $post): JsonResponse
    {
        try {
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            $likes = PostLike::query()
                ->where('post_id', $post)
                ->with('user:id,username,email,avatar')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get post likes successful',
                'data' => $likes,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get post likes failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API lấy danh sách comment của bài viết
    public function comments(int $post): JsonResponse
    {
        try {
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            $comments = PostComment::query()
                ->where('post_id', $post)
                ->with('user:id,username,email,avatar')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get post comments successful',
                'data' => $comments,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get post comments failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API lấy danh sách user đã share bài viết
    public function shares(int $post): JsonResponse
    {
        try {
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            $shares = PostShare::query()
                ->where('post_id', $post)
                ->with('user:id,username,email,avatar')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Get post shares successful',
                'data' => $shares,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get post shares failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

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
            // kèm thông tin tác giả, danh sách ảnh và bộ đếm tương tác.
            $posts = Post::query()
                ->where('user_id', $user)
                ->with(['user:id,username,email,avatar', 'images'])
                ->withCount(['likes', 'comments', 'shares'])
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

            // Validate input: caption trạng thái là tùy chọn, nhưng images bắt buộc phải có ít nhất 1 URL.
            $validated = $request->validate([
                'caption' => 'nullable|string',
                'status' => 'sometimes|in:active,hidden',
                'images' => 'required|array|min:1',
                'images.*' => 'required|string|max:255',
                'thumbnail_index' => 'nullable|integer|min:0',
            ]);

            // Dùng transaction để đảm bảo tạo post và images thành công cùng nhau.
            $post = DB::transaction(function () use ($validated, $authUser) {
                // Tạo bản ghi bài viết gốc.
                $post = Post::query()->create([
                    'user_id' => $authUser->id,
                    'caption' => $validated['caption'] ?? null,
                    'status' => $validated['status'] ?? 'active',
                ]);

                // Mặc định ảnh đầu tiên là thumbnail nếu client không truyền thumbnail_index.
                $thumbnailIndex = $validated['thumbnail_index'] ?? 0;
                $hasThumbnailColumn = Schema::hasColumn('post_images', 'is_thumbnail');

                // Chuẩn hóa mảng URL ảnh về format createMany().
                $images = array_map(
                    static function (string $imageUrl, int $index) use ($thumbnailIndex, $hasThumbnailColumn): array {
                        $row = [
                            'image_url' => $imageUrl,
                        ];

                        if ($hasThumbnailColumn) {
                            $row['is_thumbnail'] = $index === $thumbnailIndex;
                        }

                        return $row;
                    },
                    $validated['images'],
                    array_keys($validated['images'])
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
            // Lấy chi tiết post cùng thông tin user, images và các bộ đếm tương tác.
            $foundPost = Post::query()
                ->with(['user:id,username,email,avatar', 'images'])
                ->withCount(['likes', 'comments', 'shares'])
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
                'images.*' => 'required_with:images|string|max:255',
                'add_images' => 'sometimes|array|min:1',
                'add_images.*' => 'required_with:add_images|string|max:255',
                'delete_image_ids' => 'sometimes|array|min:1',
                'delete_image_ids.*' => 'required_with:delete_image_ids|integer|distinct',
                'thumbnail_index' => 'nullable|integer|min:0',
            ]);

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
            DB::transaction(function () use ($validated, $foundPost) {
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

                    if ($thumbnailIndex >= count($validated['images'])) {
                        throw ValidationException::withMessages([
                            'thumbnail_index' => ['Thumbnail index is out of range.'],
                        ]);
                    }

                    $images = array_map(
                        static function (string $imageUrl, int $index) use ($thumbnailIndex, $hasThumbnailColumn): array {
                            $row = [
                                'image_url' => $imageUrl,
                            ];

                            if ($hasThumbnailColumn) {
                                $row['is_thumbnail'] = $index === $thumbnailIndex;
                            }

                            return $row;
                        },
                        $validated['images'],
                        array_keys($validated['images'])
                    );

                    // Xóa ảnh cũ rồi thêm ảnh mới.
                    $foundPost->images()->delete();
                    $foundPost->images()->createMany($images);
                } else {
                    // Hỗ trợ update linh hoạt: thêm ảnh mới hoặc xóa theo image id.
                    if (array_key_exists('delete_image_ids', $validated)) {
                        $deleteImageIds = $validated['delete_image_ids'];

                        $ownedImageIds = $foundPost->images()
                            ->whereIn('id', $deleteImageIds)
                            ->pluck('id')
                            ->all();

                        if (count($ownedImageIds) !== count($deleteImageIds)) {
                            throw ValidationException::withMessages([
                                'delete_image_ids' => ['Some images do not belong to this post.'],
                            ]);
                        }

                        $foundPost->images()->whereIn('id', $deleteImageIds)->delete();
                    }

                    if (array_key_exists('add_images', $validated)) {
                        $newImages = array_map(
                            static function (string $imageUrl) use ($hasThumbnailColumn): array {
                                $row = [
                                    'image_url' => $imageUrl,
                                ];

                                if ($hasThumbnailColumn) {
                                    $row['is_thumbnail'] = false;
                                }

                                return $row;
                            },
                            $validated['add_images']
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

            // Xóa post, các bản ghi phụ thuộc sẽ đi theo theo rule của foreign key.
            $foundPost->delete();

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

    // API like bài viết
    public function like(Request $request, int $post): JsonResponse
    {
        try {
            // Xác thực người dùng từ token Sanctum.
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Đảm bảo bài viết tồn tại trước khi thao tác.
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Kiểm tra trạng thái hiện tại để thực hiện toggle like/unlike.
            $alreadyLiked = PostLike::query()
                ->where('post_id', $post)
                ->where('user_id', $authUser->id)
                ->exists();

            if ($alreadyLiked) {
                // Nếu đã like thì lần bấm tiếp theo sẽ bỏ like.
                PostLike::query()
                    ->where('post_id', $post)
                    ->where('user_id', $authUser->id)
                    ->delete();

                $this->refreshPostCounters($foundPost);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Unlike post successful',
                    'data' => [
                        'liked' => false,
                        'like_count' => $foundPost->like_count,
                        'comment_count' => $foundPost->comment_count,
                        'share_count' => $foundPost->shares()->count(),
                    ],
                ]);
            }

            // Tạo bản ghi like mới, sau đó đồng bộ lại bộ đếm.
            PostLike::query()->create([
                'post_id' => $post,
                'user_id' => $authUser->id,
            ]);

            $this->refreshPostCounters($foundPost);

            return response()->json([
                'status' => 'success',
                'message' => 'Like post successful',
                'data' => [
                    'liked' => true,
                    'like_count' => $foundPost->like_count,
                    'comment_count' => $foundPost->comment_count,
                    'share_count' => $foundPost->shares()->count(),
                ],
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Like post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API bỏ like bài viết
    public function unlike(Request $request, int $post): JsonResponse
    {
        try {
            // Xác thực người dùng từ token Sanctum.
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Đảm bảo bài viết tồn tại trước khi thao tác.
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Xóa like theo cặp post_id + user_id.
            $deletedCount = PostLike::query()
                ->where('post_id', $post)
                ->where('user_id', $authUser->id)
                ->delete();

            $this->refreshPostCounters($foundPost);

            return response()->json([
                'status' => 'success',
                'message' => $deletedCount > 0 ? 'Unlike post successful' : 'You have not liked this post',
                'data' => [
                    'liked' => false,
                    'like_count' => $foundPost->like_count,
                    'comment_count' => $foundPost->comment_count,
                    'share_count' => $foundPost->shares()->count(),
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Unlike post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API thêm comment cho bài viết
    public function comment(Request $request, int $post): JsonResponse
    {
        try {
            // Xác thực người dùng từ token Sanctum.
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Đảm bảo bài viết tồn tại trước khi thao tác.
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Nội dung comment bắt buộc và giới hạn độ dài.
            $validated = $request->validate([
                'content' => 'required|string|max:300',
            ]);

            // Tạo comment mới và gắn với user hiện tại.
            $createdComment = PostComment::query()->create([
                'post_id' => $post,
                'user_id' => $authUser->id,
                'content' => $validated['content'],
            ]);

            $this->refreshPostCounters($foundPost);

            return response()->json([
                'status' => 'success',
                'message' => 'Comment post successful',
                'data' => [
                    'comment' => $createdComment->load('user:id,username,email,avatar'),
                    'like_count' => $foundPost->like_count,
                    'comment_count' => $foundPost->comment_count,
                    'share_count' => $foundPost->shares()->count(),
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
                'message' => 'Comment post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API xóa comment của bài viết
    public function deleteComment(Request $request, int $post, int $comment): JsonResponse
    {
        try {
            // Xác thực người dùng từ token Sanctum.
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Đảm bảo bài viết tồn tại.
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Chỉ tìm comment thuộc đúng bài viết đang thao tác.
            $foundComment = PostComment::query()
                ->where('post_id', $post)
                ->find($comment);

            if (! $foundComment) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Comment not found',
                ], 404);
            }

            // Quyền xóa: chủ comment, chủ bài viết hoặc admin.
            $isCommentOwner = $foundComment->user_id === $authUser->id;
            $isPostOwner = $foundPost->user_id === $authUser->id;
            $isAdmin = $authUser->role === 'admin';

            if (! $isCommentOwner && ! $isPostOwner && ! $isAdmin) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Forbidden',
                ], 403);
            }

            $foundComment->delete();

            $this->refreshPostCounters($foundPost);

            return response()->json([
                'status' => 'success',
                'message' => 'Delete comment successful',
                'data' => [
                    'comment_count' => $foundPost->comment_count,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Delete comment failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // API share bài viết
    public function share(Request $request, int $post): JsonResponse
    {
        try {
            // Xác thực người dùng từ token Sanctum.
            $authUser = $request->user();

            if (! $authUser) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Đảm bảo bài viết tồn tại trước khi thao tác.
            $foundPost = Post::query()->find($post);

            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            // Chống share trùng (mỗi user share 1 lần cho 1 post).
            $alreadyShared = PostShare::query()
                ->where('post_id', $post)
                ->where('user_id', $authUser->id)
                ->exists();

            if ($alreadyShared) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Already shared this post',
                    'data' => [
                        'shared' => true,
                        'share_count' => $foundPost->shares()->count(),
                    ],
                ]);
            }

            // Ghi nhận hành vi share mới.
            PostShare::query()->create([
                'post_id' => $post,
                'user_id' => $authUser->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Share post successful',
                'data' => [
                    'shared' => true,
                    'share_count' => $foundPost->shares()->count(),
                ],
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Share post failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    // Đồng bộ bộ đếm like/comment trên bảng posts.
    private function refreshPostCounters(Post $post): void
    {
        // Đếm lại từ bảng like/comment thay vì tăng giảm tay để tránh lệch số liệu.
        $post->update([
            'like_count' => $post->likes()->count(),
            'comment_count' => $post->comments()->count(),
        ]);

        // Nạp lại model để response sau đó luôn lấy được giá trị mới nhất.
        $post->refresh();
    }
}
