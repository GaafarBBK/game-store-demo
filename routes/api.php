<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{game}', [GameController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [UserController::class, 'logout']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/games', [GameController::class, 'store']);
        Route::put('/games/{game}', [GameController::class, 'update']);
        Route::delete('/games/{game}', [GameController::class, 'destroy']);
    });

    Route::post('/games/{game}/purchase', [PurchaseController::class, 'purchase'])->middleware('role:basic_user');
    Route::get('/purchases', [PurchaseController::class, 'index'])->middleware('role:basic_user');

    Route::middleware('role:basic_user')->group(function () {
        Route::post('/games/{game}/reviews', [ReviewController::class, 'store']);
        Route::get('/games/{game}/reviews', [ReviewController::class, 'index']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
        Route::post('/games/{game}/favorite', [FavoriteController::class, 'toggleFavorite']);
        Route::get('/favorites', [FavoriteController::class, 'index']);
    });

    Route::middleware('role:manager')->group(function () {
        Route::get('/purchases', [PurchaseController::class, 'index']);
    });
});

