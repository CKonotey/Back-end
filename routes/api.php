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

// logging in as a user
Route::post('login', [Login::class, 'login']);

Route::middleware('auth:api')->group(function () {
    // For querying and creating a new chat
    // Route::post('ai/ask', [aiController::class, 'querySlide']);

    // For updating an existing chat
    Route::put('ai/chat/update', [aiController::class, 'updateChat']);

    // for creating a new chat
    Route::post('ai/chat/createchatorupdateconvo', [aiController::class, 'getOrCreateChat']);

    // For fetching user's chat history
    Route::get('ai/chats', [aiController::class, 'getUserChats']);
    Route::get('ai/chats/{id}', [aiController::class, 'showChat']);


    // logging out of the system
    Route::post('logout', [Login::class, 'logout']);

    // uploading of slides and creating a folder for the user
    Route::post('ai/upload-slide', [SlideController::class, 'uploadSlide']);
});


Route::post('forgot-password', [PasswordResetController::class, 'forgotPassword']);
Route::post('reset-password', [PasswordResetController::class, 'resetPassword']);






Route::get('/', [SlideController::class, 'index']);
Route::get('/slides', [SlideController::class, 'index']);
Route::post('/slides/search', [SlideController::class, 'store']);

