<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Services\CartService;
use App\Services\DeliveryFeeCalculator;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, CartService $cart, DeliveryFeeCalculator $fees): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()
                ->route('restaurants.index')
                ->withErrors(['cart' => 'Ton panier est vide.']);
        }

        $restaurant = $cart->restaurant();

        if (! $restaurant) {
            return redirect()
                ->route('restaurants.index')
                ->withErrors(['cart' => 'Restaurant du panier introuvable.']);
        }

        $deliveryLat = $request->filled('delivery_lat') ? (float) $request->input('delivery_lat') : null;
        $deliveryLng = $request->filled('delivery_lng') ? (float) $request->input('delivery_lng') : null;
        $deliveryFee = $fees->forRestaurant($restaurant, $deliveryLat, $deliveryLng);
        $distanceKm = null;

        if ($deliveryLat !== null && $deliveryLng !== null
            && $restaurant->latitude !== null && $restaurant->longitude !== null) {
            $distanceKm = $fees->distanceKm(
                (float) $restaurant->latitude,
                (float) $restaurant->longitude,
                $deliveryLat,
                $deliveryLng
            );
        }

        return view('checkout.show', [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $deliveryFee,
            'total' => $cart->subtotal() + $deliveryFee,
            'deliveryLat' => $deliveryLat,
            'deliveryLng' => $deliveryLng,
            'distanceKm' => $distanceKm,
        ]);
    }

    public function store(PlaceOrderRequest $request, CartService $cart, OrderService $orderService): RedirectResponse
    {
        try {
            $order = $orderService->placeFromCart(
                $request->user(),
                $cart,
                $request->validated()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['checkout' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Commande confirmée et payée.');
    }
}
