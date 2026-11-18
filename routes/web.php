<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Middleware\ResolveBlogSite;


// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(ResolveBlogSite::class)->prefix('/')->group(function () {
    Route::get('sitemap.xml', [\App\Http\Controllers\CanvasUiController::class, 'sitemap'])
         ->name('sitemap');

    Route::prefix('api')->group(function () {
        Route::get('posts', [\App\Http\Controllers\CanvasUiController::class, 'getPosts']);
        Route::get('posts/{slug}', [\App\Http\Controllers\CanvasUiController::class, 'showPost'])
             ->middleware('Canvas\Http\Middleware\Session');

        Route::get('tags', [\App\Http\Controllers\CanvasUiController::class, 'getTags']);
        Route::get('tags/{slug}', [\App\Http\Controllers\CanvasUiController::class, 'showTag']);
        Route::get('tags/{slug}/posts', [\App\Http\Controllers\CanvasUiController::class, 'getPostsForTag']);

        Route::get('topics', [\App\Http\Controllers\CanvasUiController::class, 'getTopics']);
        Route::get('topics/{slug}', [\App\Http\Controllers\CanvasUiController::class, 'showTopic']);
        Route::get('topics/{slug}/posts', [\App\Http\Controllers\CanvasUiController::class, 'getPostsForTopic']);

        Route::get('users/{id}', [\App\Http\Controllers\CanvasUiController::class, 'showUser']);
        Route::get('users/{id}/posts', [\App\Http\Controllers\CanvasUiController::class, 'getPostsForUser']);

        // Comment routes
        Route::get('comments', [CommentController::class, 'index']);
        Route::get('comments/{id}', [CommentController::class, 'show']);
        Route::post('comments', [CommentController::class, 'store']);
        
        // Authenticated comment routes
        Route::middleware(['auth'])->group(function () {
            Route::put('comments/{id}', [CommentController::class, 'update']);
            Route::delete('comments/{id}', [CommentController::class, 'destroy']);
            Route::put('comments/{id}/approve', [CommentController::class, 'approve']);
        });
    });

    // Admin routes for comment management
    Route::prefix('canvas')->middleware(['canvas', 'auth'])->group(function () {
        Route::get('comments', function () {
            return inertia('Canvas::Comments/Index');
        });
        
        Route::get('comments/pending', function () {
            return inertia('Canvas::Comments/Pending');
        });
    });

    // Catch-all route for the frontend
    Route::get('/{view?}', [\App\Http\Controllers\CanvasUiController::class, 'index'])
         ->where('view', '(.*)')
         ->name('home');
});
