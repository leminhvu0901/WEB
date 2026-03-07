<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:300',
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $post->update([
            'comment_count' => $post->comments()->count(),
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user'),
            'comment_count' => $post->fresh()->comment_count,
        ], 201);
    }

    public function destroy(Request $request, PostComment $comment): JsonResponse
    {
        abort_unless((int) $request->user()->id === (int) $comment->user_id, 403, 'You cannot delete this comment.');

        $post = $comment->post;
        $comment->delete();

        $post->update([
            'comment_count' => $post->comments()->count(),
        ]);

        return response()->json([
            'message' => 'Comment deleted successfully',
            'comment_count' => $post->fresh()->comment_count,
        ]);
    }
}
