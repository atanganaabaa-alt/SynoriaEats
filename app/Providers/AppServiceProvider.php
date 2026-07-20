<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Listeners\SendOrderConfirmationNotification;
use App\Services\CartService;
use App\Services\Notifications\LogNotifier;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\OrangeSmsNotifier;
use App\Services\Notifications\TwilioSmsNotifier;
use App\Services\Notifications\TwilioWhatsAppNotifier;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(Notifier::class, function ($app) {
            $channels = [];
            $configured = collect(explode(',', (string) config('synoria.notifications.channels', 'log')))
                ->map(fn (string $channel) => trim($channel))
                ->filter()
                ->all();

            foreach ($configured as $channel) {
                $channels[] = match ($channel) {
                    'sms' => $app->make(TwilioSmsNotifier::class),
                    'whatsapp' => $app->make(TwilioWhatsAppNotifier::class),
                    'orange_sms' => $app->make(OrangeSmsNotifier::class),
                    default => new LogNotifier($channel === 'log' ? 'log' : $channel),
                };
            }

            if ($channels === []) {
                $channels[] = $app->make(LogNotifier::class);
            }

            return new NotificationDispatcher($channels);
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
