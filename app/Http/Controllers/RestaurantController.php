<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString() ?: 'rating';

        $restaurants = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->whereHas('menuItems', fn ($query) => $query->where('is_available', true))
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('min_rating'), fn ($query) => $query->where('rating', '>=', (float) $request->input('min_rating')))
            ->when($request->filled('max_fee'), fn ($query) => $query->where('delivery_fee', '<=', (int) $request->input('max_fee')))
            ->when($sort === 'fee', fn ($query) => $query->orderBy('delivery_fee')->orderByDesc('rating'))
            ->when($sort === 'prep', fn ($query) => $query->orderBy('prep_time_min')->orderByDesc('rating'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when(! in_array($sort, ['fee', 'prep', 'name'], true), fn ($query) => $query->orderByDesc('rating'))
            ->paginate(12)
            ->withQueryString();

        $categories = Restaurant::query()
            ->where('is_open', true)
            ->where('is_validated', true)
            ->whereHas('menuItems', fn ($query) => $query->where('is_available', true))
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('restaurants.index', compact('restaurants', 'categories'));
    }

    public function show(Restaurant $restaurant): View
    {
        abort_unless($restaurant->is_validated || auth()->user()?->isAdmin() || auth()->user()?->id === $restaurant->owner_id, 404);

        $restaurant->load(['menuItems' => fn ($query) => $query->where('is_available', true)->orderBy('category')->orderBy('name')]);

        return view('restaurants.show', compact('restaurant'));
    }
}
