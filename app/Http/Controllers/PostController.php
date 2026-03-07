<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = Post::with(['user', 'images', 'comments.user'])
            ->withCount(['likes', 'comments', 'shares'])
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $posts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'caption' => 'nullable|string',
            'status' => 'nullable|in:active,hidden',
            'images' => 'nullable|array',
            'images.*.image_url' => 'required_with:images|string|max:255',
            'images.*.is_thumbnail' => 'nullable|boolean',
        ]);

        if (empty($validated['caption']) && empty($validated['images'])) {
            return response()->json([
                'message' => 'Caption or at least one image is required.',
            ], 422);
        }

        $post = DB::transaction(function () use ($request, $validated) {
            $post = Post::create([
                'user_id' => $request->user()->id,
                'caption' => $validated['caption'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'like_count' => 0,
                'comment_count' => 0,
            ]);

            foreach ($validated['images'] ?? [] as $image) {
                $post->images()->create([
                    'image_url' => $image['image_url'],
                    'is_thumbnail' => $image['is_thumbnail'] ?? false,
                ]);
            }

            return $post;
        });

        return response()->json([
            'message' => 'Post created successfully',
            'data' => $post->load(['user', 'images']),
        ], 201);
    }

    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'data' => $post->load(['user', 'images', 'comments.user'])->loadCount(['likes', 'comments', 'shares']),
        ]);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        abort_unless((int) $request->user()->id === (int) $post->user_id, 403, 'You cannot update this post.');

        $validated = $request->validate([
            'caption' => 'nullable|string',
            'status' => 'nullable|in:active,hidden',
            'images' => 'nullable|array',
            'images.*.image_url' => 'required_with:images|string|max:255',
            'images.*.is_thumbnail' => 'nullable|boolean',
        ]);

        $post = DB::transaction(function () use ($validated, $post) {
            $post->update([
                'caption' => $validated['caption'] ?? $post->caption,
                'status' => $validated['status'] ?? $post->status,
            ]);

            if (array_key_exists('images', $validated)) {
                $post->images()->delete();

                foreach ($validated['images'] ?? [] as $image) {
                    $post->images()->create([
                        'image_url' => $image['image_url'],
                        'is_thumbnail' => $image['is_thumbnail'] ?? false,
                    ]);
                }
            }

            return $post;
        });

        return response()->json([
            'message' => 'Post updated successfully',
            'data' => $post->load(['user', 'images']),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        abort_unless((int) $request->user()->id === (int) $post->user_id, 403, 'You cannot delete this post.');

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully',
        ]);
    }
}
