<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login'])->middleware('throttle:login');
Route::get('/games', [GameController::class, 'search']);
Route::get('/games/{game}', [GameController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/logout', [UserController::class, 'logout']);

    Route::middleware('role:admin,manager')->group(function () {
        Route::put('/games/{game}', [GameController::class, 'update']);
        Route::delete('/games/{game}', [GameController::class, 'destroy']);
    });

    
    Route::middleware('role:basic_user')->group(function () {
        Route::post('/games/purchase', [PurchaseController::class, 'store']);
        Route::post('/games/redeem', [PurchaseController::class, 'redeemCode']);

        Route::post('/games/{game}/reviews', [ReviewController::class, 'store']);
        Route::get('/games/{game}/reviews', [ReviewController::class, 'index']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        Route::post('/games/{game}/favorite', [FavoriteController::class, 'toggleFavorite']);
        Route::get('/favorites', [FavoriteController::class, 'index']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/genres', [GameController::class, 'storeGenre']);
        Route::post('/platforms', [GameController::class, 'storePlatform']);
        Route::post('/cryptocurrencies', [GameController::class, 'storeCryptocurrency']);
    });

    Route::middleware('role:manager')->group(function () {
        Route::post('/games', [GameController::class, 'store']);
    });

    Route::middleware('role:basic_user,manager')->group(function () {
        Route::get('/purchases/games', [PurchaseController::class, 'index']);    
    });

    
});

