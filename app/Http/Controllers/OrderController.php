<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['restaurant', 'items'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless($order->customer_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $order->load(['restaurant', 'items', 'courier', 'review']);

        return view('orders.show', compact('order'));
    }
}
