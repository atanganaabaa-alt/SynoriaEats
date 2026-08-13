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
use Illuminate\Support\Facades\DB;

class MenuItemController extends Controller
{
    public function __construct(private CloudinaryUploader $media) {}

    public function create(Request $request, Restaurant $restaurant): View
    {
        $this->authorizeOwner($request, $restaurant);

        $accompaniments = $restaurant->menuItems()
            ->where('category', MenuCategory::Accompagnements->value)
            ->orderBy('name')
            ->get();

        return view('owner.menu-items.create', [
            'restaurant' => $restaurant,
            'categories' => MenuCategory::cases(),
            'accompaniments' => $accompaniments,
        ]);
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant): RedirectResponse
    {
        $data = $request->menuItemAttributes($restaurant);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $this->media->upload($request->file('photo'), 'menu-items');
        }

        $menuItem = MenuItem::query()->create($data);

        $this->syncAccompanimentsFromRequest($request, $menuItem, $restaurant);

        return redirect()
            ->route('owner.restaurants.show', $restaurant)
            ->with('status', 'Article ajouté au menu.');
    }

    public function edit(Request $request, MenuItem $menuItem): View
    {
        $menuItem->load(['restaurant', 'accompanimentOptions']);
        $this->authorizeOwner($request, $menuItem->restaurant);

        $accompaniments = $menuItem->restaurant->menuItems()
            ->where('category', MenuCategory::Accompagnements->value)
            ->orderBy('name')
            ->get();

        return view('owner.menu-items.edit', [
            'restaurant' => $menuItem->restaurant,
            'menuItem' => $menuItem,
            'categories' => MenuCategory::cases(),
            'accompaniments' => $accompaniments,
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

        $menuItem->refresh();
        $this->syncAccompanimentsFromRequest($request, $menuItem, $menuItem->restaurant);

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
        abort_unless($request->user()->can('update', $restaurant), 403);
    }

    private function syncAccompanimentsFromRequest(Request $request, MenuItem $menuItem, Restaurant $restaurant): void
    {
        // Seuls les "Plats" peuvent avoir des accompagnements liés.
        if ($menuItem->category !== MenuCategory::Plats->value) {
            $menuItem->accompanimentOptions()->detach();
            return;
        }

        $accompanimentIds = $request->input('accompaniment_option_ids', []);
        if (! is_array($accompanimentIds)) {
            $accompanimentIds = [];
        }

        $extraPrices = $request->input('accompaniment_option_extra_prices', []);
        if (! is_array($extraPrices)) {
            $extraPrices = [];
        }

        if ($accompanimentIds === []) {
            $menuItem->accompanimentOptions()->detach();
            return;
        }

        // On ne garde que les IDs d’accompagnements qui appartiennent à ce restaurant.
        $validAccompaniments = $restaurant->menuItems()
            ->where('category', MenuCategory::Accompagnements->value)
            ->whereIn('id', $accompanimentIds)
            ->pluck('id')
            ->all();

        $sync = [];
        foreach ($validAccompaniments as $accompId) {
            $sync[$accompId] = [
                'extra_price' => (int) ($extraPrices[$accompId] ?? 0),
                'is_available' => true,
            ];
        }

        DB::transaction(function () use ($menuItem, $sync): void {
            $menuItem->accompanimentOptions()->sync($sync);
        });
    }
}
