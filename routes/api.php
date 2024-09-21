<?php

use App\Http\Controllers\aiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Login;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\Register;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\SlidesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Register a user
Route::post('register', [Register::class, 'register']);

Route::post('login', [Login::class, 'login']);
Route::middleware(['auth:api'])->group(function () {
    Route::post('logout', [Login::class, 'logout']);
});

Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/query-slide', [SlidesController::class, 'querySlide']);
Route::get('/query-slide/show', [SlidesController::class, 'index']);
Route::post('/upload-slide', [SlidesController::class, 'uploadSlide']);
Route::post('/ai/ask', [aiController::class, 'querySlide']);



Route::get('/', [SlideController::class, 'index']);

// Route::middleware('auth:sanctum')->group(function () {
//     // Other protected routes...
// });
Route::get('/slides', [SlideController::class, 'index']);
Route::post('/slides/search', [SlideController::class, 'store']);


// Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

// Route::post('/logout', [AuthController::class, 'logout'])
//         ->middleware('auth:sanctum');

