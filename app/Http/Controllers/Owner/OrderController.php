<?php

namespace App\Http\Controllers\Owner;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['restaurant', 'customer', 'items'])
            ->whereHas('restaurant', fn ($q) => $q->where('owner_id', $request->user()->id))
            ->latest()
            ->paginate(15);

        return view('owner.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless(
            $request->user()->isAdmin() || $order->restaurant->owner_id === $request->user()->id,
            403
        );

        $order->load(['customer', 'restaurant', 'items']);

        $nextStatuses = match ($order->status) {
            OrderStatus::Pending => [OrderStatus::Accepted, OrderStatus::Cancelled],
            OrderStatus::Accepted => [OrderStatus::Preparing, OrderStatus::Cancelled],
            OrderStatus::Preparing => [OrderStatus::Ready, OrderStatus::Cancelled],
            default => [],
        };

        return view('owner.orders.show', compact('order', 'nextStatuses'));
    }

    public function update(UpdateOrderStatusRequest $request, Order $order, OrderService $orderService): RedirectResponse
    {
        try {
            $orderService->updateStatus(
                $order,
                OrderStatus::from($request->validated('status')),
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('owner.orders.show', $order)
            ->with('status', 'Statut mis à jour.');
    }
}
