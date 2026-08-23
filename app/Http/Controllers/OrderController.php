<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
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

        $order->load(['restaurant', 'items', 'courier', 'review', 'statusEvents.actor']);

        return view('orders.show', compact('order'));
    }

    public function tracking(Request $request, Order $order): JsonResponse
    {
        $this->assertCanTrack($request, $order);

        $live = $order->isLiveTrackingActive();
        $order->loadMissing(['courier', 'restaurant', 'statusEvents.actor']);

        return response()->json([
            'number' => $order->number,
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'sharing_active' => $live,
            'courier' => $order->courier ? [
                'name' => $order->courier->name,
                'phone' => $order->courier->phone,
                'rating' => $order->courier->rating,
            ] : null,
            'courier_lat' => $live ? $order->courier_lat : null,
            'courier_lng' => $live ? $order->courier_lng : null,
            'customer_lat' => $live ? ($order->customer_lat ?? $order->delivery_lat) : null,
            'customer_lng' => $live ? ($order->customer_lng ?? $order->delivery_lng) : null,
            'delivery_lat' => $order->delivery_lat,
            'delivery_lng' => $order->delivery_lng,
            'restaurant' => [
                'name' => $order->restaurant->name,
                'lat' => $order->restaurant->latitude,
                'lng' => $order->restaurant->longitude,
            ],
            'timeline' => $order->statusEvents->map(fn ($event) => [
                'to_status' => $event->to_status->value,
                'to_status_label' => $event->to_status->label(),
                'note' => $event->note,
                'actor' => $event->actor?->name,
                'at' => $event->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
                'current' => $event->to_status === $order->status,
            ]),
            'updated_at' => $order->updated_at?->toIso8601String(),
        ]);
    }

    public function location(Request $request, Order $order, OrderService $orders): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $orders->updateCustomerLocation(
                $order,
                $request->user(),
                (float) $validated['lat'],
                (float) $validated['lng']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'sharing_active' => true]);
    }

    private function assertCanTrack(Request $request, Order $order): void
    {
        $user = $request->user();

        abort_unless(
            $order->customer_id === $user->id
            || $order->courier_id === $user->id
            || $order->restaurant->owner_id === $user->id
            || $user->isAdmin(),
            403
        );
    }
}
