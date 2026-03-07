<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //

    public function index()
    {
        $users = User::query()
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $users,
        ]);
    }
}
