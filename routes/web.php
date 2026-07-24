<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Courier\MissionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Owner\MenuItemController as OwnerMenuItemController;
use App\Http\Controllers\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Owner\RestaurantController as OwnerRestaurantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ReviewController;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
Route::get('/restaurants/{restaurant:slug}', [RestaurantController::class, 'show'])->name('restaurants.show');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/dashboard', function () {
    $user = auth()->user();

    return match ($user?->role) {
        UserRole::RestaurantOwner => redirect()->route('owner.restaurants.index'),
        UserRole::Courier => redirect()->route('courier.missions.index'),
        UserRole::Admin => redirect()->route('restaurants.index'),
        default => redirect()->route('restaurants.index'),
    };
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{menuItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking'])->name('orders.tracking');
    Route::post('/orders/{order}/reviews', [ReviewController::class, 'store'])->name('orders.reviews.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:courier,admin')->prefix('courier')->name('courier.')->group(function () {
        Route::get('missions', [MissionController::class, 'index'])->name('missions.index');
        Route::get('missions/{order}', [MissionController::class, 'show'])->name('missions.show');
        Route::post('missions/{order}/claim', [MissionController::class, 'claim'])->name('missions.claim');
        Route::post('missions/{order}/pickup', [MissionController::class, 'pickup'])->name('missions.pickup');
        Route::post('missions/{order}/deliver', [MissionController::class, 'deliver'])->name('missions.deliver');
        Route::post('missions/{order}/location', [MissionController::class, 'location'])->name('missions.location');
    });

    Route::middleware('role:restaurant_owner,admin')->prefix('owner')->name('owner.')->group(function () {
        Route::get('orders', [OwnerOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OwnerOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}', [OwnerOrderController::class, 'update'])->name('orders.update');

        Route::get('restaurants/{restaurant}/menu-items/create', [OwnerMenuItemController::class, 'create'])
            ->name('menu-items.create');
        Route::post('restaurants/{restaurant}/menu-items', [OwnerMenuItemController::class, 'store'])
            ->name('menu-items.store');
        Route::get('menu-items/{menuItem}/edit', [OwnerMenuItemController::class, 'edit'])
            ->name('menu-items.edit');
        Route::put('menu-items/{menuItem}', [OwnerMenuItemController::class, 'update'])
            ->name('menu-items.update');
        Route::delete('menu-items/{menuItem}', [OwnerMenuItemController::class, 'destroy'])
            ->name('menu-items.destroy');

        Route::resource('restaurants', OwnerRestaurantController::class);
    });
});

require __DIR__.'/auth.php';
