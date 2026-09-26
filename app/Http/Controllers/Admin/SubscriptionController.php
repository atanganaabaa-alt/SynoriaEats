<?php

namespace App\Http\Controllers\Admin;

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

    public function index(Request $request): View
    {
        $filter = $request->string('status')->toString();

        $restaurants = Restaurant::query()
            ->with('owner')
            ->where('is_validated', true)
            ->when($filter === 'trial', fn ($q) => $q->where('trial_ends_at', '>', now())
                ->where(fn ($inner) => $inner->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now())))
            ->when($filter === 'active', fn ($q) => $q->where('subscription_ends_at', '>', now()))
            ->when($filter === 'expired', fn ($q) => $q
                ->where(fn ($inner) => $inner->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now()))
                ->where(fn ($inner) => $inner->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now())))
            ->latest('reviewed_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'trial' => Restaurant::query()->where('is_validated', true)->where('trial_ends_at', '>', now())
                ->where(fn ($q) => $q->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now()))->count(),
            'active' => Restaurant::query()->where('subscription_ends_at', '>', now())->count(),
            'expired' => Restaurant::query()->where('is_validated', true)
                ->where(fn ($q) => $q->whereNull('trial_ends_at')->orWhere('trial_ends_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('subscription_ends_at')->orWhere('subscription_ends_at', '<=', now()))->count(),
        ];

        return view('admin.subscriptions.index', [
            'restaurants' => $restaurants,
            'stats' => $stats,
            'plans' => SubscriptionPlan::cases(),
            'trialDays' => $this->subscriptions->trialDays(),
        ]);
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ]);

        $this->subscriptions->subscribe($restaurant, SubscriptionPlan::from($validated['plan']));

        return back()->with('status', 'Abonnement mis à jour pour '.$restaurant->name.'.');
    }
}
