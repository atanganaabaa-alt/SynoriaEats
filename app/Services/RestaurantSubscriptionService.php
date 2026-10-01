<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;

class RestaurantSubscriptionService
{
    public function trialDays(): int
    {
        return (int) config('synoria.subscriptions.trial_days', 30);
    }

    public function startTrial(Restaurant $restaurant, ?Carbon $from = null): Restaurant
    {
        $from ??= now();

        if ($restaurant->trial_ends_at !== null) {
            return $restaurant;
        }

        $restaurant->update([
            'trial_ends_at' => $from->copy()->addDays($this->trialDays()),
        ]);

        return $restaurant->fresh();
    }

    public function subscribe(Restaurant $restaurant, SubscriptionPlan $plan, ?Carbon $from = null): Restaurant
    {
        $from ??= now();

        $candidates = array_filter([
            $from->getTimestamp(),
            $restaurant->subscription_ends_at?->getTimestamp(),
            $restaurant->trial_ends_at?->getTimestamp(),
        ]);

        $startTs = max($candidates);
        $start = Carbon::createFromTimestamp($startTs);
        if ($start->lt($from)) {
            $start = $from->copy();
        }

        $restaurant->update([
            'subscription_plan' => $plan,
            'subscription_ends_at' => $start->copy()->addMonth(),
        ]);

        return $restaurant->fresh();
    }
}
