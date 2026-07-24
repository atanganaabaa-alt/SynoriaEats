<?php

namespace App\Http\Controllers\Owner;

use App\Enums\MenuCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Services\CloudinaryUploader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuItemController extends Controller
{
    public function __construct(private CloudinaryUploader $media) {}

    public function create(Request $request, Restaurant $restaurant): View
    {
        $this->authorizeOwner($request, $restaurant);

        return view('owner.menu-items.create', [
            'restaurant' => $restaurant,
            'categories' => MenuCategory::cases(),
        ]);
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->menuItemAttributes($restaurant);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $this->media->upload($request->file('photo'), 'menu-items');
        }

        MenuItem::query()->create($data);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Article ajouté au menu.');
    }

    public function edit(Request $request, MenuItem $menuItem): View
    {
        $menuItem->load('restaurant');
        $this->authorizeOwner($request, $menuItem->restaurant);

        return view('owner.menu-items.edit', [
            'restaurant' => $menuItem->restaurant,
            'menuItem' => $menuItem,
            'categories' => MenuCategory::cases(),
        ]);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        $data = [
            'name' => $request->string('name')->toString(),
            'description' => $request->input('description'),
            'price' => (int) $request->input('price'),
            'category' => $request->input('category', MenuCategory::Plats->value),
            'is_available' => $request->boolean('is_available'),
        ];

        if ($request->hasFile('photo')) {
            $this->media->deleteIfLocal($menuItem->photo_url);
            $data['photo_url'] = $this->media->upload($request->file('photo'), 'menu-items');
        }

        $menuItem->update($data);

        return redirect()
            ->route('owner.restaurants.show', $menuItem->restaurant)
            ->with('status', 'Article mis à jour.');
    }

    public function destroy(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->load('restaurant');
        $this->authorizeOwner($request, $menuItem->restaurant);

        $restaurant = $menuItem->restaurant;
        $this->media->deleteIfLocal($menuItem->photo_url);
        $menuItem->delete();

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Article supprimé.');
    }

    private function authorizeOwner(Request $request, Restaurant $restaurant): void
    {
        abort_unless(
            $request->user()->isAdmin() || $restaurant->owner_id === $request->user()->id,
            403
        );
    }
}
