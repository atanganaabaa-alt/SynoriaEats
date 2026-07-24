<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRestaurantRequest;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString() ?: 'rating';

        $restaurants = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('min_rating'), fn ($q) => $q->where('rating', '>=', (float) $request->input('min_rating')))
            ->when($request->filled('max_fee'), fn ($q) => $q->where('delivery_fee', '<=', (int) $request->input('max_fee')))
            ->when($sort === 'fee', fn ($q) => $q->orderBy('delivery_fee')->orderByDesc('rating'))
            ->when($sort === 'name', fn ($q) => $q->orderBy('name'))
            ->when(! in_array($sort, ['fee', 'name'], true), fn ($q) => $q->orderByDesc('rating'))
            ->paginate(20);

        return response()->json($restaurants);
    }

    public function show(Restaurant $restaurant): JsonResponse
    {
        abort_unless($restaurant->is_validated, 404);

        $restaurant->load(['menuItems' => fn ($q) => $q->where('is_available', true)->orderBy('category')]);

        return response()->json($restaurant);
    }

    public function store(StoreRestaurantRequest $request): JsonResponse
    {
        $data = $request->validatedRestaurant();

        if ($request->hasFile('logo')) {
            $data['logo_url'] = $request->file('logo')->store('restaurants/logos', 'public');
        }

        if ($request->hasFile('cover')) {
            $data['cover_url'] = $request->file('cover')->store('restaurants/covers', 'public');
        }

        $restaurant = Restaurant::query()->create($data);

        return response()->json($restaurant, 201);
    }

    public function update(UpdateRestaurantRequest $request, Restaurant $restaurant): JsonResponse
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

        return response()->json($restaurant->fresh());
    }
}
