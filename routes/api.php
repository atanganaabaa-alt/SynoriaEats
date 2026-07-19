<?php

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Api\MenuItemController as ApiMenuItemController;
use App\Http\Controllers\Api\RestaurantController as ApiRestaurantController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [ApiAuthController::class, 'register']);
Route::post('/login', [ApiAuthController::class, 'login']);

Route::get('/restaurants', [ApiRestaurantController::class, 'index']);
Route::get('/restaurants/{restaurant:slug}', [ApiRestaurantController::class, 'show']);
Route::get('/restaurants/{restaurant:slug}/menu-items', [ApiMenuItemController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [ApiAuthController::class, 'me']);
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    Route::middleware('role:restaurant_owner,admin')->group(function () {
        Route::post('/restaurants', [ApiRestaurantController::class, 'store']);
        Route::put('/restaurants/{restaurant}', [ApiRestaurantController::class, 'update']);
        Route::post('/restaurants/{restaurant}/menu-items', [ApiMenuItemController::class, 'store']);
        Route::put('/menu-items/{menuItem}', [ApiMenuItemController::class, 'update']);
        Route::delete('/menu-items/{menuItem}', [ApiMenuItemController::class, 'destroy']);
    });
});
