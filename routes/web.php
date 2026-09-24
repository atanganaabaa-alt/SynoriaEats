<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\CourierController as AdminCourierController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\RestaurantController as AdminRestaurantController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\AiConversationController;
use App\Http\Controllers\Courier\MissionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Owner\MenuItemController as OwnerMenuItemController;
use App\Http\Controllers\Owner\OnboardingController as OwnerOnboardingController;
use App\Http\Controllers\Owner\OrderController as OwnerOrderController;
use App\Http\Controllers\Owner\RestaurantController as OwnerRestaurantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RestaurantController;
use App\Http\Controllers\ReviewController;
use App\Models\Restaurant;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/', function () {
    $featured = Restaurant::query()
        ->where('is_open', true)
        ->where('is_validated', true)
        ->whereHas('menuItems', fn ($q) => $q->where('is_available', true))
        ->with(['menuItems' => fn ($q) => $q->where('is_available', true)->limit(1)])
        ->latest()
        ->limit(6)
        ->get();

    return view('welcome', compact('featured'));
})->name('home');

Route::get('/restaurants', [RestaurantController::class, 'index'])->name('restaurants.index');
Route::get('/selection', [RestaurantController::class, 'preferences'])->name('restaurants.preferences');
Route::post('/selection', [RestaurantController::class, 'savePreferences'])->name('restaurants.preferences.store');
Route::delete('/selection', [RestaurantController::class, 'resetPreferences'])->name('restaurants.preferences.reset');
Route::get('/restaurants/{restaurant:slug}', [RestaurantController::class, 'show'])->name('restaurants.show');

Route::get('/companion', [AiConversationController::class, 'show'])->name('companion.show');
Route::get('/companion/history', [AiConversationController::class, 'history'])->name('companion.history');
Route::post('/companion/message', [AiConversationController::class, 'message'])->name('companion.message');
Route::post('/companion/reset', [AiConversationController::class, 'reset'])->name('companion.reset');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

Route::get('/dashboard', function () {
    $user = auth()->user();

    if ($user?->isRestaurantOwner()) {
        if ($user->ownedRestaurants()->doesntExist()) {
            return redirect()->route('owner.onboarding');
        }

        if (! $user->isApproved()) {
            return redirect()->route('owner.pending');
        }

        return redirect()->route('owner.restaurants.index');
    }

    if ($user?->isCourier()) {
        return $user->isApproved()
            ? redirect()->route('courier.missions.index')
            : redirect()->route('courier.pending');
    }

    return match ($user?->role) {
        UserRole::Admin => redirect()->route('admin.dashboard'),
        default => redirect()->route('restaurants.index'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
        Route::patch('/cart/line/{lineKey}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart', [CartController::class, 'destroy'])->name('cart.destroy');

    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/tracking', [OrderController::class, 'tracking'])->name('orders.tracking');
    Route::post('/orders/{order}/location', [OrderController::class, 'location'])->name('orders.location');
    Route::post('/orders/{order}/reviews', [ReviewController::class, 'store'])->name('orders.reviews.store');

    Route::get('/notifications/poll', [\App\Http\Controllers\LiveNotificationController::class, 'poll'])
        ->name('notifications.poll');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::get('restaurants', [AdminRestaurantController::class, 'index'])->name('restaurants.index');
        Route::get('restaurants/{restaurant}', [AdminRestaurantController::class, 'show'])->name('restaurants.show');
        Route::patch('restaurants/{restaurant}', [AdminRestaurantController::class, 'update'])->name('restaurants.update');
        Route::get('documents/{document}', [\App\Http\Controllers\Admin\RestaurantDocumentController::class, 'show'])
            ->name('documents.show');
        Route::get('couriers', [AdminCourierController::class, 'index'])->name('couriers.index');
        Route::post('couriers', [AdminCourierController::class, 'store'])->name('couriers.store');
        Route::patch('couriers/{user}', [AdminCourierController::class, 'update'])->name('couriers.update');
        Route::get('commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    });

    Route::middleware('role:courier')->prefix('courier')->name('courier.')->group(function () {
        Route::get('pending', fn () => view('courier.pending'))->name('pending');
    });

    Route::middleware(['role:courier,admin', 'approved'])->prefix('courier')->name('courier.')->group(function () {
        Route::get('missions', [MissionController::class, 'index'])->name('missions.index');
        Route::get('missions/{order}', [MissionController::class, 'show'])->name('missions.show');
        Route::post('missions/{order}/claim', [MissionController::class, 'claim'])->name('missions.claim');
        Route::post('missions/{order}/pickup', [MissionController::class, 'pickup'])->name('missions.pickup');
        Route::post('missions/{order}/deliver', [MissionController::class, 'deliver'])->name('missions.deliver');
        Route::post('missions/{order}/location', [MissionController::class, 'location'])->name('missions.location');
    });

    Route::middleware('role:restaurant_owner,admin')->prefix('owner')->name('owner.')->group(function () {
        Route::get('pending', [OwnerOnboardingController::class, 'pending'])->name('pending');
        Route::get('onboarding', [OwnerOnboardingController::class, 'create'])->name('onboarding');
        Route::post('onboarding', [OwnerOnboardingController::class, 'store'])->name('onboarding.store');
        Route::post('geocode', \App\Http\Controllers\Owner\GeocodeController::class)->name('geocode');
    });

    Route::middleware(['role:restaurant_owner,admin', 'approved'])->prefix('owner')->name('owner.')->group(function () {
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
