<?php

use App\Http\Controllers\SlideController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/godwin', function () {
    return view('godwin');
});

// Fetch slide data by slide ID
// Route::get('/slides/{slide_id}', [SlideController::class, 'getSlideData']);

// Query slide content based on user query
Route::get('/slides', [SlideController::class, 'index']);
Route::post('/slides/search', [SlideController::class, 'searchSlideContent']);
Route::get('/slides/{id}', [SlideController::class, 'show']);
// Route::middleware(['cors'])->group(function () {
//     Route::get('/slides', [SlideController::class, 'index']);
// });

