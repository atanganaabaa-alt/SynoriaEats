<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $restaurants = Restaurant::query()
            ->where('is_open', true)
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';

                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->orderByDesc('rating')
            ->paginate(12)
            ->withQueryString();

        $categories = Restaurant::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('restaurants.index', compact('restaurants', 'categories'));
    }

    public function show(Restaurant $restaurant): View
    {
        $restaurant->load(['menuItems' => fn ($query) => $query->where('is_available', true)->orderBy('category')->orderBy('name')]);

        return view('restaurants.show', compact('restaurant'));
    }
}
