<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Restaurant;
use App\Services\CloudinaryUploader;
use App\Services\RestaurantGeocoder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function __construct(
        private CloudinaryUploader $media,
        private RestaurantGeocoder $geocoder,
    ) {}

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
            $data['logo_url'] = $this->media->upload($request->file('logo'), 'restaurants/logos');
        }

        if ($request->hasFile('cover')) {
            $data['cover_url'] = $this->media->upload($request->file('cover'), 'restaurants/covers');
        }

        $data = $this->geocoder->applyToRestaurantData($data);

        $restaurant = Restaurant::query()->create($data);

        $message = $restaurant->isApproved()
            ? 'Restaurant créé avec position carte.'
            : 'Restaurant créé avec position carte. Il reste en attente de validation admin avant d’apparaître au catalogue.';

        if ($restaurant->latitude === null || $restaurant->longitude === null) {
            $message .= ' Attention : adresse non localisée. Modifie l’adresse et reclique « Placer sur la carte ».';
        }

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', $message);
    }

    public function show(Request $request, Restaurant $restaurant): View
    {
        $this->authorize('view', $restaurant);

        $restaurant->load(['menuItems' => fn ($q) => $q->latest()]);

        return view('owner.restaurants.show', compact('restaurant'));
    }

    public function edit(Request $request, Restaurant $restaurant): View
    {
        $this->authorize('view', $restaurant);

        return view('owner.restaurants.edit', compact('restaurant'));
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $this->authorize('update', $restaurant);

        $data = $request->safe()->except(['logo', 'cover']);

        if ($request->has('is_open')) {
            $data['is_open'] = $restaurant->isApproved()
                ? $request->boolean('is_open')
                : false;
        }

        if ($request->hasFile('logo')) {
            $this->media->deleteIfLocal($restaurant->logo_url);
            $data['logo_url'] = $this->media->upload($request->file('logo'), 'restaurants/logos');
        }

        if ($request->hasFile('cover')) {
            $this->media->deleteIfLocal($restaurant->cover_url);
            $data['cover_url'] = $this->media->upload($request->file('cover'), 'restaurants/covers');
        }

        $addressChanged = isset($data['address']) && $data['address'] !== $restaurant->address;
        $missingCoords = $restaurant->latitude === null || $restaurant->longitude === null;
        $manualCoords = filled($data['latitude'] ?? null) && filled($data['longitude'] ?? null);

        if ($manualCoords) {
            // Pin glissé / placé depuis le formulaire
        } elseif ($addressChanged || $missingCoords) {
            $data = $this->geocoder->applyToRestaurantData([
                ...$restaurant->only(['address', 'latitude', 'longitude']),
                ...$data,
            ], force: $addressChanged);
        }

        $restaurant->update($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Restaurant mis à jour.');
    }

    public function destroy(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $this->authorize('delete', $restaurant);
        $restaurant->delete();

        return redirect()
            ->route('owner.restaurants.index')
            ->with('status', 'Restaurant supprimé.');
    }
}
