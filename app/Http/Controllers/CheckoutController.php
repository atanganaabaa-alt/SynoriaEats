<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(CartService $cart): View|RedirectResponse
    {
        if ($cart->isEmpty()) {
            return redirect()
                ->route('restaurants.index')
                ->withErrors(['cart' => 'Ton panier est vide.']);
        }

        return view('checkout.show', [
            'lines' => $cart->lines(),
            'subtotal' => $cart->subtotal(),
            'deliveryFee' => $cart->deliveryFee(),
            'total' => $cart->total(),
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
