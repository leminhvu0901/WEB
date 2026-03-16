<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// AUTH
Route::post('/register', [AuthController::class, 'register']); //them
Route::post('/login', [AuthController::class, 'login']);//dang nhap

// USERS
Route::get('/users', [UserController::class, 'index']);//ds
Route::get('/users/{user}', [UserController::class, 'show']);//ds 1 user
Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']); //cap nhat
Route::delete('/users/{user}', [UserController::class, 'destroy']); //xóa

// FOLLOW
Route::get('/users/{user}/followers', [FollowController::class, 'followers']); // danh sách fl 1 users
Route::get('/users/{user}/following', [FollowController::class, 'following']); // danh sách da fl cua 1 users

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/me', [AuthController::class, 'me']); //thong tin user hien tai dang dang nhap
	Route::post('/users/{user}/follow', [FollowController::class, 'follow']); //fl
	Route::delete('/users/{user}/follow', [FollowController::class, 'unfollow']); //xoa fl
});
