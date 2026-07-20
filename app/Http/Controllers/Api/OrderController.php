<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class OrderController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['restaurant', 'items'])
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless(
            $order->customer_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );

        return response()->json($order->load(['restaurant', 'items']));
    }

    public function addToCart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'menu_item_id' => ['required', 'exists:menu_items,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        try {
            $this->cart->add(
                MenuItem::query()->findOrFail($validated['menu_item_id']),
                (int) ($validated['quantity'] ?? 1)
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['cart' => $this->cart->summary()]);
    }

    public function cart(): JsonResponse
    {
        return response()->json($this->cart->summary());
    }

    public function place(PlaceOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orders->place($request->user(), $request->validated());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($order, 201);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {
            $order = $this->orders->transition($order, OrderStatus::from($request->validated('status')));
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($order);
    }
}
