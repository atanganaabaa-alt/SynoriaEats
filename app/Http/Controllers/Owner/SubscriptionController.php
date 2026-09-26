<?php

namespace App\Http\Controllers\Owner;

use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\RestaurantSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private RestaurantSubscriptionService $subscriptions,
    ) {}

    public function show(Request $request, Restaurant $restaurant): View
    {
        $this->authorize('view', $restaurant);

        return view('owner.restaurants.subscription', [
            'restaurant' => $restaurant,
            'plans' => SubscriptionPlan::cases(),
            'trialDays' => $this->subscriptions->trialDays(),
        ]);
    }

    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $this->authorize('update', $restaurant);

        if (! $restaurant->isApproved()) {
            return back()->withErrors(['plan' => 'Le restaurant doit d’abord être approuvé.']);
        }

        $validated = $request->validate([
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ]);

        $plan = SubscriptionPlan::from($validated['plan']);

        // Paiement réel (MoMo/agrégateur) à brancher plus tard — sandbox : active tout de suite
        $this->subscriptions->subscribe($restaurant, $plan);

        return redirect()
            ->route('owner.restaurants.subscription', $restaurant)
            ->with('status', __('Abonnement :plan activé jusqu’au :date (paiement sandbox).', [
                'plan' => $plan->label(),
                'date' => $restaurant->fresh()->subscription_ends_at?->format('d/m/Y'),
            ]));
    }
}
