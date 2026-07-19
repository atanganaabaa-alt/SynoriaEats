<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $restaurants = Restaurant::query()
            ->where('owner_id', $request->user()->id)
            ->latest()
            ->get();

        return view('owner.restaurants.index', compact('restaurants'));
    }

    public function create(): View
    {
        return view('owner.restaurants.create');
    }

    public function store(StoreRestaurantRequest $request): RedirectResponse
    {
        $data = $request->validatedRestaurant();

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        if ($request->hasFile('cover')) {
            $data['cover_url'] = $request->file('cover')->store('restaurants/covers', 'public');
        }

        $restaurant = Restaurant::query()->create($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Restaurant créé.');
    }

    public function show(Request $request, Restaurant $restaurant): View
    {
        $this->authorizeOwner($request, $restaurant);

        $restaurant->load(['menuItems' => fn ($q) => $q->latest()]);

        return view('owner.restaurants.show', compact('restaurant'));
    }

    public function edit(Request $request, Restaurant $restaurant): View
    {
        $this->authorizeOwner($request, $restaurant);

        return view('owner.restaurants.edit', compact('restaurant'));
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->safe()->except(['logo', 'cover']);
        $data['is_open'] = $request->boolean('is_open');

        if ($request->hasFile('logo')) {
            if ($restaurant->logo_url) {
                Storage::disk('public')->delete($restaurant->logo_url);
            }
            $data['logo_url'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        if ($request->hasFile('cover')) {
            if ($restaurant->cover_url) {
                Storage::disk('public')->delete($restaurant->cover_url);
            }
            $data['cover_url'] = $request->file('cover')->store('restaurants/covers', 'public');
        }

        $restaurant->update($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Restaurant mis à jour.');
    }

    public function destroy(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $this->authorizeOwner($request, $restaurant);
        $restaurant->delete();

        return redirect()
            ->route('owner.restaurants.index')
            ->with('status', 'Restaurant supprimé.');
    }

    private function authorizeOwner(Request $request, Restaurant $restaurant): void
    {
        abort_unless(
            $request->user()->isAdmin() || $restaurant->owner_id === $request->user()->id,
            403
        );
    }
}
