<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->withCount(['posts', 'followers', 'following'])
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->loadCount(['posts', 'followers', 'following']),
        ]);
    }

    public function posts(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->posts()->with(['images', 'user'])->latest('id')->get(),
        ]);
    }

    public function followers(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->followers()->get(),
        ]);
    }

    public function following(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->following()->get(),
        ]);
    }
}
