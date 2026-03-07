<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
    public function toggle(Request $request, Post $post): JsonResponse
    {
        $existing = $post->likes()->where('user_id', $request->user()->id)->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $post->likes()->create([
                'user_id' => $request->user()->id,
            ]);
            $liked = true;
        }

        $count = $post->likes()->count();
        $post->update(['like_count' => $count]);

        return response()->json([
            'message' => $liked ? 'Post liked successfully' : 'Post unliked successfully',
            'liked' => $liked,
            'like_count' => $count,
        ]);
    }
}
