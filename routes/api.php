<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PostController;
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

// POSTS
Route::get('/posts/{post}', [PostController::class, 'show']); // xem 1 bài viết cụ thể
Route::get('/users/{user}/posts', [PostController::class, 'byUser']); // xem tất cả bài viết của 1 user

Route::get('/posts/{post}/likes', [PostController::class, 'likes']); // danh sách user đã like bài viết
Route::get('/posts/{post}/comments', [PostController::class, 'comments']); // danh sách comment của bài viết
Route::get('/posts/{post}/shares', [PostController::class, 'shares']); // danh sách user đã share bài viết

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/me', [AuthController::class, 'me']); //thong tin user hien tai dang dang nhap

    //FOLLOW
	Route::post('/users/{user}/follow', [FollowController::class, 'follow']); //fl
	Route::delete('/users/{user}/follow', [FollowController::class, 'unfollow']); //xoa fl


	Route::post('/posts', [PostController::class, 'add']); // tạo bài viết mới (nhiều ảnh)
	Route::match(['put', 'patch'], '/posts/{post}', [PostController::class, 'update']); // cập nhật bài viết
	Route::delete('/posts/{post}', [PostController::class, 'destroy']); // xóa bài viết
    
	Route::post('/posts/{post}/like', [PostController::class, 'like']); // like bài viết
	Route::delete('/posts/{post}/like', [PostController::class, 'unlike']); // bỏ like bài viết
	Route::post('/posts/{post}/comments', [PostController::class, 'comment']); // thêm comment bài viết
	Route::delete('/posts/{post}/comments/{comment}', [PostController::class, 'deleteComment']); // xóa comment
	Route::post('/posts/{post}/share', [PostController::class, 'share']); // share bài viết
});
