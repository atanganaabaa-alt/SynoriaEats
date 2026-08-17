<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Restaurant;
use App\Services\CloudinaryUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function __construct(private CloudinaryUploader $media) {}

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

        $restaurant = Restaurant::query()->create($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Restaurant créé.');
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
        $data['is_open'] = $request->boolean('is_open');

        if ($request->hasFile('logo')) {
            $this->media->deleteIfLocal($restaurant->logo_url);
            $data['logo_url'] = $this->media->upload($request->file('logo'), 'restaurants/logos');
        }

        if ($request->hasFile('cover')) {
            $this->media->deleteIfLocal($restaurant->cover_url);
            $data['cover_url'] = $this->media->upload($request->file('cover'), 'restaurants/covers');
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
