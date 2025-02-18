<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ====================================
// Public Routes (No Authentication Required)
// ====================================

// Authentication
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login'])->middleware('throttle:login');

// Game Browsing
Route::get('/games', [GameController::class, 'search']);
Route::get('/games/top-rated', [GameController::class, 'topRatedGames']);
Route::get('/games/{game}', [GameController::class, 'show']);

// ====================================
// Protected Routes (Requires Authentication)
// ====================================

Route::middleware('auth:sanctum')->group(function () {

    // =========================
    // Authentication
    // =========================
    Route::post('/logout', [UserController::class, 'logout']);

    // =========================
    // Game Management (Admins & Managers)
    // =========================
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/games', [GameController::class, 'store']);
        Route::put('/games/{game}', [GameController::class, 'update']);
        Route::delete('/games/{game}', [GameController::class, 'destroy']);
    });

    // =========================
    // User Actions (Basic Users)
    // =========================
    Route::middleware('role:basic_user')->group(function () {
        
        // Game Purchases & Redemptions
        Route::post('/games/purchase', [PurchaseController::class, 'store']);
        Route::post('/games/redeem', [PurchaseController::class, 'redeemCode']);

        // Reviews
        Route::post('/games/{game}/reviews', [ReviewController::class, 'store']);
        Route::get('/games/{game}/reviews', [ReviewController::class, 'index']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        // Favorites
        Route::post('/games/{game}/favorite', [FavoriteController::class, 'toggleFavorite']);
        Route::get('/favorites', [FavoriteController::class, 'index']);
    });

    // =========================
    // Admin Actions (Metadata Management)
    // =========================
    Route::middleware('role:admin')->group(function () {
        Route::post('/genres', [GameController::class, 'storeGenre']);
        Route::post('/platforms', [GameController::class, 'storePlatform']);
        Route::post('/cryptocurrencies', [GameController::class, 'storeCryptocurrency']);
    });

    // =========================
    // Purchase History (Basic Users & Managers)
    // =========================
    Route::middleware('role:basic_user,manager')->group(function () {
        Route::get('/purchases/games', [PurchaseController::class, 'index']);    
    });

});
