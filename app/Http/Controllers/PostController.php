<?php

namespace App\Http\Controllers;

use App\Http\Requests\Post\StorePostRequest;
use App\Models\Post;
use App\Models\PostImage;
use App\Support\StoresOriginalFileNames;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Throwable;

class PostController extends Controller
{
    use StoresOriginalFileNames;

    private function hasPostImagesTable(): bool
    {
        return Schema::hasTable('post_images');
    }

    private function postImageDirectory(int $postId): string
    {
        return 'uploads/posts/post-'.$postId;
    }

    private function createPostCompatible(array $payload): Post
    {
        try {
            return Post::query()->create($payload);
        } catch (QueryException $e) {
            // Legacy schema may define `posts.id` without AUTO_INCREMENT.
            if (! str_contains($e->getMessage(), "Field 'id' doesn't have a default value")) {
                throw $e;
            }

            $nextId = ((int) Post::query()->max('id')) + 1;
            Post::query()->insert(['id' => $nextId] + $payload);

            /** @var Post $created */
            $created = Post::query()->findOrFail($nextId);

            return $created;
        }
    }

    private function attachLegacyImages(Post $post): Post
    {
        if (! Schema::hasColumn('posts', 'image')) {
            $post->setRelation('images', collect());

            return $post;
        }

        $legacyImagePath = $post->getAttribute('image');

        if (empty($legacyImagePath)) {
            $post->setRelation('images', collect());

            return $post;
        }

        $post->setRelation('images', collect([
            new PostImage([
                'image_url' => $legacyImagePath,
                'is_thumbnail' => true,
            ]),
        ]));

        return $post;
    }

    private function attachLegacyImagesToMany(Collection $posts): Collection
    {
        return $posts->map(function (Post $post): Post {
            return $this->attachLegacyImages($post);
        });
    }
    // Lấy danh sách tất cả bài viết
    public function index(): JsonResponse
    {
        try {
            $hasPostImagesTable = $this->hasPostImagesTable();

            $query = Post::query()
                ->with('user:id,username,email,avatar')
                ->orderByDesc('id');

            if ($hasPostImagesTable) {
                $query->with('images');
            }

            $posts = $query->get();

            if (! $hasPostImagesTable) {
                $posts = $this->attachLegacyImagesToMany($posts);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Get all posts successful',
                'data' => $posts,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Get all posts failed',
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

            $hasPostImagesTable = $this->hasPostImagesTable();

            // Lấy tất cả bài viết của user theo user_id,
            // kèm thông tin tác giả và danh sách ảnh.
            $query = Post::query()
                ->where('user_id', $user)
                ->with('user:id,username,email,avatar')
                ->orderByDesc('id');

            if ($hasPostImagesTable) {
                $query->with('images');
            }

            $posts = $query->get();

            if (! $hasPostImagesTable) {
                $posts = $this->attachLegacyImagesToMany($posts);
            }

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

            // Ho tro schema cu gui 1 file qua field `image`.
            if (empty($uploadedImages) && $request->hasFile('image')) {
                $uploadedImages = [$request->file('image')];
            }

            $thumbnailIndex = $validated['thumbnail_index'] ?? 0;

            if (!empty($uploadedImages) && $thumbnailIndex >= count($uploadedImages)) {
                throw ValidationException::withMessages([
                    'thumbnail_index' => ['Thumbnail index is out of range.'],
                ]);
            }

            $hasPostImagesTable = $this->hasPostImagesTable();
            $hasStatusColumn = Schema::hasColumn('posts', 'status');
            $hasLegacyImageColumn = Schema::hasColumn('posts', 'image');

            // Dùng transaction để đảm bảo tạo post và images thành công cùng nhau.
            $post = DB::transaction(function () use ($validated, $authUser, $uploadedImages, $thumbnailIndex, $hasPostImagesTable, $hasStatusColumn, $hasLegacyImageColumn) {
                $postPayload = [
                    'user_id' => $authUser->id,
                    'caption' => $validated['caption'] ?? null,
                ];

                if ($hasStatusColumn) {
                    $postPayload['status'] = $validated['status'] ?? 'active';
                }

                // Tạo bản ghi bài viết gốc.
                $post = $this->createPostCompatible($postPayload);

                if (! $hasPostImagesTable) {
                    if ($hasLegacyImageColumn && isset($uploadedImages[$thumbnailIndex])) {
                        $post->forceFill([
                            'image' => $this->storePublicFileWithOriginalName(
                                $uploadedImages[$thumbnailIndex],
                                $this->postImageDirectory($post->id)
                            ),
                        ])->save();
                    }

                    return $post;
                }

                $hasThumbnailColumn = Schema::hasColumn('post_images', 'is_thumbnail');

                // Chuẩn hóa mảng file ảnh về format createMany().
                $images = [];
                foreach ($uploadedImages as $index => $imageFile) {
                    $row = [
                        'image_url' => $this->storePublicFileWithOriginalName(
                            $imageFile,
                            $this->postImageDirectory($post->id)
                        ),
                    ];

                    if ($hasThumbnailColumn) {
                        $row['is_thumbnail'] = (int) $index === (int) $thumbnailIndex;
                    }

                    $images[] = $row;
                }

                // Tạo toàn bộ ảnh liên kết với post.
                $post->images()->createMany($images);

                return $post;
            });

            $post = $post->load('user:id,username,email,avatar');

            if ($hasPostImagesTable) {
                $post->load('images');
            } else {
                $this->attachLegacyImages($post);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Create post successful',
                // Trả về kèm user và images để client không cần gọi thêm API.
                'data' => $post,
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
            $hasPostImagesTable = $this->hasPostImagesTable();

            // Lấy chi tiết post cùng thông tin user và images.
            $query = Post::query()->with('user:id,username,email,avatar');

            if ($hasPostImagesTable) {
                $query->with('images');
            }

            $foundPost = $query->find($post);

            // Không tìm thấy post thì trả 404 JSON.
            if (! $foundPost) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Post not found',
                ], 404);
            }

            if (! $hasPostImagesTable) {
                $this->attachLegacyImages($foundPost);
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

            $hasPostImagesTable = $this->hasPostImagesTable();
            $imagePaths = [];

            if ($hasPostImagesTable) {
                $imagePaths = $foundPost->images()
                    ->pluck('image_url')
                    ->filter() //bỏ giá trị rỗng
                    ->all(); //thành mảng PHP
            } elseif (Schema::hasColumn('posts', 'image') && ! empty($foundPost->image)) {
                $imagePaths = [$foundPost->image];
            }

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
