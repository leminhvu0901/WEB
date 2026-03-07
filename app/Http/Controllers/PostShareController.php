<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostShareController extends Controller
{
    public function store(Request $request, Post $post): JsonResponse
    {
        $share = $post->shares()->create([
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Post shared successfully',
            'data' => $share,
            'share_count' => $post->shares()->count(),
        ], 201);
    }
}
