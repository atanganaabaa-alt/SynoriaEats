<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    public function index(Restaurant $restaurant): JsonResponse
    {
        return response()->json(
            $restaurant->menuItems()->where('is_available', true)->orderBy('category')->get()
        );
    }

    public function store(StoreMenuItemRequest $request, Restaurant $restaurant): JsonResponse
    {
        $data = $request->safe()->except(['photo']);
        $data['restaurant_id'] = $restaurant->id;
        $data['is_available'] = $request->boolean('is_available', true);

        if ($request->hasFile('photo')) {
            $data['photo_url'] = $request->file('photo')->store('menu-items', 'public');
        }

        $item = MenuItem::query()->create($data);

        return response()->json($item, 201);
    }

    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        $data = $request->safe()->except(['photo']);
        $data['is_available'] = $request->boolean('is_available');

        if ($request->hasFile('photo')) {
            if ($menuItem->photo_url) {
                Storage::disk('public')->delete($menuItem->photo_url);
            }
            $data['photo_url'] = $request->file('photo')->store('menu-items', 'public');
        }

        $menuItem->update($data);

        return response()->json($menuItem->fresh());
    }

    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        abort_unless(
            $request->user()->isAdmin()
                || $menuItem->restaurant->owner_id === $request->user()->id,
            403
        );

        $menuItem->delete();

        return response()->json(null, 204);
    }
}
