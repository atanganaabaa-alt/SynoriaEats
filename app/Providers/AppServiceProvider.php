<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderConfirmationNotification;
use App\Services\CartService;
use App\Services\Notifications\LogNotifier;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\TwilioNotifier;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Notifier::class, function ($app) {
            if (config('synoria.notifications.driver') === 'twilio'
                && filled(config('services.twilio.sid'))) {
                return $app->make(TwilioNotifier::class);
            }

            return $app->make(LogNotifier::class);
        });
    }

    public function boot(): void
    {
        Event::listen(OrderPlaced::class, [SendOrderConfirmationNotification::class, 'handleOrderPlaced']);
        Event::listen(OrderStatusChanged::class, [SendOrderConfirmationNotification::class, 'handleOrderStatusChanged']);

        View::composer('layouts.navigation', function ($view) {
            if (auth()->check()) {
                $view->with('cartCount', app(CartService::class)->count());
            }
        });
    }
}
