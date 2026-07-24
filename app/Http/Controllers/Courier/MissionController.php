<?php

namespace App\Http\Controllers\Courier;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MissionController extends Controller
{
    public function index(Request $request): View
    {
        $courier = $request->user();

        $available = Order::query()
            ->with(['restaurant', 'items'])
            ->where('status', OrderStatus::Ready)
            ->whereNull('courier_id')
            ->latest()
            ->get();

        $mine = Order::query()
            ->with(['restaurant', 'items', 'customer'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', [OrderStatus::Ready, OrderStatus::OutForDelivery])
            ->latest()
            ->get();

        $history = Order::query()
            ->with(['restaurant'])
            ->where('courier_id', $courier->id)
            ->where('status', OrderStatus::Delivered)
            ->latest('delivered_at')
            ->limit(10)
            ->get();

        return view('courier.missions.index', compact('available', 'mine', 'history'));
    }

    public function show(Request $request, Order $order): View
    {
        $this->assertCanView($request, $order);

        $order->load(['restaurant', 'items', 'customer']);

        return view('courier.missions.show', compact('order'));
    }

    public function claim(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        try {
            $orders->claim($order, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['mission' => $e->getMessage()]);
        }

        return redirect()
            ->route('courier.missions.show', $order)
            ->with('status', 'Mission acceptée. Rends-toi au restaurant.');
    }

    public function pickup(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        try {
            $orders->startDelivery($order, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['mission' => $e->getMessage()]);
        }

        return redirect()
            ->route('courier.missions.show', $order)
            ->with('status', 'Livraison démarrée.');
    }

    public function deliver(Request $request, Order $order, OrderService $orders): RedirectResponse
    {
        try {
            $orders->completeDelivery($order, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['mission' => $e->getMessage()]);
        }

        return redirect()
            ->route('courier.missions.index')
            ->with('status', 'Livraison terminée. Bravo !');
    }

    public function location(Request $request, Order $order, OrderService $orders): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $orders->updateCourierLocation(
                $order,
                $request->user(),
                (float) $validated['lat'],
                (float) $validated['lng']
            );
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['mission' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('status', 'Position mise à jour.');
    }

    private function assertCanView(Request $request, Order $order): void
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return;
        }

        $isAssigned = $order->courier_id === $user->id;
        $isAvailable = $order->status === OrderStatus::Ready && $order->courier_id === null;

        abort_unless($isAssigned || $isAvailable, 403);
    }
}
