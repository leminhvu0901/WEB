<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function toggle(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();

        if ((int) $authUser->id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot follow yourself.',
            ], 422);
        }

        $existing = $authUser->following()->where('users.id', $user->id)->first();

        if ($existing) {
            $authUser->following()->detach($user->id);
            $following = false;
        } else {
            $authUser->following()->attach($user->id, ['created_at' => now()]);
            $following = true;
        }

        return response()->json([
            'message' => $following ? 'Followed successfully' : 'Unfollowed successfully',
            'following' => $following,
            'follower_count' => $user->followers()->count(),
        ]);
    }
}
