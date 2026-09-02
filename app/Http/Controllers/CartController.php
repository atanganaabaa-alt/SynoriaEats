<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(CartService $cart): View
    {
        return view('cart.show', [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $cart->deliveryFee(),
            'total' => $cart->total(),
        ]);
    }

    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'exists:menu_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
            'accompaniment_ids' => ['nullable', 'array'],
            'accompaniment_ids.*' => ['integer', 'min:1', 'exists:menu_items,id'],
        ]);

        $menuItem = MenuItem::query()->findOrFail($validated['menu_item_id']);

        try {
            $accompanimentIds = $validated['accompaniment_ids'] ?? [];

            if ($menuItem->category === \App\Enums\MenuCategory::Plats->value && $accompanimentIds !== []) {
                $cart->addDishWithAccompaniments($menuItem, $validated['quantity'] ?? 1, $accompanimentIds);
            } else {
                $cart->add($menuItem, $validated['quantity'] ?? 1);
            }
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['menu_item_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('cart.show')
            ->with('status', __('Plat ajouté au panier.'));
    }

    public function update(Request $request, string $lineKey, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);

        $cart->updateQuantity($lineKey, (int) $validated['quantity']);

        return redirect()->route('cart.show')->with('status', __('Panier mis à jour.'));
    }

    public function destroy(CartService $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()->route('restaurants.index')->with('status', __('Panier vidé.'));
    }
}
