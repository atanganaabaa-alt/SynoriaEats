<?php

namespace App\Http\Controllers;

use App\Enums\MenuCategory;
use App\Models\Restaurant;
use App\Services\RestaurantMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    private const MATCH_KEYS = ['distance', 'price', 'fee', 'courier', 'rating'];

    public function index(Request $request, RestaurantMatcher $matcher): View
    {
        if ($request->filled(['lat', 'lng'])) {
            $request->session()->put('client_lat', (float) $request->input('lat'));
            $request->session()->put('client_lng', (float) $request->input('lng'));
        }

        $lat = $request->filled('lat')
            ? (float) $request->input('lat')
            : ($request->session()->get('client_lat') ? (float) $request->session()->get('client_lat') : null);
        $lng = $request->filled('lng')
            ? (float) $request->input('lng')
            : ($request->session()->get('client_lng') ? (float) $request->session()->get('client_lng') : null);

        $hasLocation = $lat !== null && $lng !== null;
        $matchWeights = $request->session()->get('match_weights', $matcher->defaultWeights());
        $matchPreset = $request->session()->get('match_preset', 'balanced');
        $hasCustomPreferences = $request->session()->has('match_weights') || $request->session()->has('match_preset');

        $query = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->whereHas('menuItems', fn ($q) => $q->where('is_available', true))
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('min_rating'), fn ($q) => $q->where('rating', '>=', (float) $request->input('min_rating')))
            ->when($request->filled('max_fee'), fn ($q) => $q->where('delivery_fee', '<=', (int) $request->input('max_fee')));

        if ($hasLocation) {
            $ranked = $matcher->rank($query->get(), $lat, $lng, $matchWeights);

            if ($ranked->isEmpty()) {
                $ranked = $matcher->rank($query->get(), $lat, $lng, $matchWeights, strictDistance: false);
            }

            $page = max(1, (int) $request->integer('page', 1));
            $perPage = 12;
            $restaurants = new LengthAwarePaginator(
                $ranked->forPage($page, $perPage)->values(),
                $ranked->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $restaurants = $query
                ->orderByDesc('rating')
                ->paginate(12)
                ->withQueryString();
        }

        return view('restaurants.index', compact(
            'restaurants',
            'lat',
            'lng',
            'hasLocation',
            'matchPreset',
            'hasCustomPreferences',
        ));
    }

    public function preferences(Request $request, RestaurantMatcher $matcher): View
    {
        $matchWeights = $request->session()->get('match_weights', $matcher->defaultWeights());
        $matchPreset = $request->session()->get('match_preset', 'balanced');
        $hasCustomPreferences = $request->session()->has('match_weights') || $request->session()->has('match_preset');
        $presets = RestaurantMatcher::PRESETS;

        return view('restaurants.preferences', compact('matchWeights', 'matchPreset', 'presets', 'hasCustomPreferences'));
    }

    public function savePreferences(Request $request, RestaurantMatcher $matcher): RedirectResponse
    {
        $validated = $request->validate([
            'pref_distance' => ['required', 'integer', 'min:0', 'max:4'],
            'pref_price' => ['required', 'integer', 'min:0', 'max:4'],
            'pref_fee' => ['required', 'integer', 'min:0', 'max:4'],
            'pref_courier' => ['required', 'integer', 'min:0', 'max:4'],
            'pref_rating' => ['required', 'integer', 'min:0', 'max:4'],
        ]);

        $weights = $matcher->normalizeWeights([
            'distance' => (int) $validated['pref_distance'],
            'price' => (int) $validated['pref_price'],
            'fee' => (int) $validated['pref_fee'],
            'courier' => (int) $validated['pref_courier'],
            'rating' => (int) $validated['pref_rating'],
        ]);

        $request->session()->put('match_weights', $weights);

        $presetKey = collect(RestaurantMatcher::PRESETS)->search(
            fn (array $preset) => $preset['weights'] === $weights
        );

        if ($presetKey !== false) {
            $request->session()->put('match_preset', $presetKey);
        } else {
            $request->session()->forget('match_preset');
        }

        return redirect()
            ->route('restaurants.index')
            ->with('status', __('Tes préférences ont été prises en compte.'));
    }

    public function resetPreferences(Request $request): RedirectResponse
    {
        $request->session()->forget(['match_weights', 'match_preset']);

        return redirect()
            ->route('restaurants.index')
            ->with('status', __('Retour à la sélection automatique.'));
    }

    public function show(Restaurant $restaurant): View
    {
        abort_unless($restaurant->is_validated || auth()->user()?->isAdmin() || auth()->user()?->id === $restaurant->owner_id, 404);

        $restaurant->load([
            'menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->where('category', '!=', MenuCategory::Accompagnements->value)
                ->orderBy('category')
                ->orderBy('name'),
            'menuItems.accompanimentOptions',
        ]);

        return view('restaurants.show', compact('restaurant'));
    }
}
