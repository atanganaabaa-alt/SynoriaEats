<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
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

    public function tracking(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return response()->json([
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'courier' => $order->courier ? [
                'name' => $order->courier->name,
                'phone' => $order->courier->phone,
                'rating' => $order->courier->rating,
            ] : null,
            'courier_lat' => $order->courier_lat,
            'courier_lng' => $order->courier_lng,
            'delivery_lat' => $order->delivery_lat,
            'delivery_lng' => $order->delivery_lng,
            'restaurant' => [
                'name' => $order->restaurant->name,
                'lat' => $order->restaurant->latitude,
                'lng' => $order->restaurant->longitude,
            ],
            'updated_at' => $order->updated_at?->toIso8601String(),
        ]);
    }
}
