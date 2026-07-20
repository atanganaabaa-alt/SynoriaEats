<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function create(Request $request, Restaurant $restaurant): View
    {
        $this->authorizeOwner($request, $restaurant);

        return view('owner.menu-items.create', compact('restaurant'));
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->menuItemAttributes($restaurant);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $request->file('photo')->store('menu-items', 'public');
        }

        MenuItem::query()->create($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Plat ajouté au menu.');
    }

    public function edit(Request $request, MenuItem $menuItem): View
    {
        $menuItem->load('restaurant');
        $this->authorizeOwner($request, $menuItem->restaurant);

        return view('owner.menu-items.edit', [
            'restaurant' => $menuItem->restaurant,
            'menuItem' => $menuItem,
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $data = [
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'price' => (int) $request->input('price'),
            'category' => $request->input('category', 'Plats'),
            'is_available' => $request->boolean('is_available'),
        ];

        if ($request->hasFile('photo')) {
            if ($menuItem->photo_url) {
                Storage::disk('public')->delete($menuItem->photo_url);
            }
            $data['photo_url'] = $request->file('photo')->store('menu-items', 'public');
        }

        $menuItem->update($data);

        return redirect()
            ->route('owner.restaurants.show', $menuItem->restaurant)
            ->with('status', 'Plat mis à jour.');
    }

    public function destroy(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->load('restaurant');
        $this->authorizeOwner($request, $menuItem->restaurant);

        $restaurant = $menuItem->restaurant;
        $menuItem->delete();

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Plat supprimé.');
    }

    private function authorizeOwner(Request $request, Restaurant $restaurant): void
    {
        abort_unless(
            $request->user()->isAdmin() || $restaurant->owner_id === $request->user()->id,
            403
        );
    }
}
