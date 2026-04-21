<?php

use App\Http\Controllers\AuthController;
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

// POSTS
Route::get('/posts', [PostController::class, 'index']); // lấy tất cả bài viết
Route::get('/posts/{post}', [PostController::class, 'show']); // xem 1 bài viết cụ thể
Route::get('/users/{user}/posts', [PostController::class, 'byUser']); // xem tất cả bài viết của 1 user

Route::middleware('auth:sanctum')->group(function () {
	Route::get('/me', [AuthController::class, 'me']); //thong tin user hien tai dang dang nhap

	Route::post('/posts', [PostController::class, 'add']); // tạo bài viết mới (nhiều ảnh)
	Route::delete('/posts/{post}', [PostController::class, 'destroy']); // xóa bài viết

});

// bfsdhbfhsbdjfhbsjbf
