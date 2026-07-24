<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $restaurants = Restaurant::query()
            ->with('owner')
            ->withCount('menuItems')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q').'%';
                $query->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)
                        ->orWhere('address', 'like', $term);
                });
            })
            ->when($request->filled('validated'), function ($query) use ($request) {
                $query->where('is_validated', $request->string('validated') === '1');
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.restaurants.index', compact('restaurants'));
    }

    public function update(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $validated = $request->validate([
            'is_validated' => ['sometimes', 'boolean'],
            'is_open' => ['sometimes', 'boolean'],
        ]);

        $data = [];
        if ($request->has('is_validated')) {
            $data['is_validated'] = $request->boolean('is_validated');
        }
        if ($request->has('is_open')) {
            $data['is_open'] = $request->boolean('is_open');
        }

        $restaurant->update($data);

        $message = 'Restaurant mis à jour.';
        if (array_key_exists('is_validated', $data)) {
            $message = $data['is_validated']
                ? 'Restaurant validé — visible au catalogue.'
                : 'Validation retirée — masqué du catalogue.';
        }

        return back()->with('status', $message);
    }
}
