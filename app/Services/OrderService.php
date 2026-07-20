<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private PaymentManager $payments,
    ) {}

    /**
     * @param  array{delivery_address: string, delivery_phone: string, payment_method: string, payment_phone?: string|null, notes?: string|null}  $data
     */
    public function placeFromCart(User $customer, CartService $cart, array $data): Order
    {
        if ($cart->isEmpty()) {
            throw new InvalidArgumentException('Ton panier est vide.');
        }

        $lines = $cart->lines();

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('Les plats du panier ne sont plus disponibles.');
        }

        $subtotal = $cart->subtotal();
        $deliveryFee = $cart->deliveryFee();
        $commission = (int) round($subtotal * config('synoria.commission_rate', 0.10));
        $total = $subtotal + $deliveryFee;
        $paymentMethod = PaymentMethod::from($data['payment_method']);
        $paymentPhone = $data['payment_phone'] ?? $data['delivery_phone'];

        $order = DB::transaction(function () use ($customer, $cart, $data, $lines, $subtotal, $deliveryFee, $commission, $total, $paymentMethod) {
            $order = Order::query()->create([
                'number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'restaurant_id' => $cart->restaurantId(),
                'delivery_address' => $data['delivery_address'],
                'delivery_phone' => $data['delivery_phone'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'commission' => $commission,
                'total' => $total,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending,
                'status' => OrderStatus::Pending,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'menu_item_id' => $line['menu_item']->id,
                    'name' => $line['menu_item']->name,
                    'unit_price' => $line['menu_item']->price,
                    'quantity' => $line['quantity'],
                ]);
            }

            return $order;
        });

        $result = $this->payments->charge($order, $paymentMethod, $paymentPhone);

        if ($result->success) {
            $order->update([
                'payment_status' => PaymentStatus::Paid,
                'payment_reference' => $result->reference,
            ]);
            $cart->clear();
            OrderPlaced::dispatch($order->fresh(['customer', 'restaurant.owner', 'items']));
        } else {
            $order->update([
                'payment_status' => PaymentStatus::Failed,
            ]);

            throw new InvalidArgumentException($result->message ?? 'Paiement refusé.');
        }

        return $order->fresh(['items', 'restaurant']);
    }

    public function updateStatus(Order $order, OrderStatus $status, User $actor): Order
    {
        $this->assertCanManage($order, $actor);
        $this->assertValidTransition($order->status, $status);

        $previous = $order->status;
        $order->update(['status' => $status]);

        OrderStatusChanged::dispatch($order->fresh(), $previous);

        return $order->fresh();
    }

    private function generateNumber(): string
    {
        return 'SE-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    private function assertCanManage(Order $order, User $actor): void
    {
        if ($actor->isAdmin()) {
            return;
        }

        abort_unless(
            $actor->isRestaurantOwner() && $order->restaurant->owner_id === $actor->id,
            403
        );
    }

    private function assertValidTransition(OrderStatus $from, OrderStatus $to): void
    {
        $allowed = match ($from) {
            OrderStatus::Pending => [OrderStatus::Accepted, OrderStatus::Cancelled],
            OrderStatus::Accepted => [OrderStatus::Preparing, OrderStatus::Cancelled],
            OrderStatus::Preparing => [OrderStatus::Ready, OrderStatus::Cancelled],
            OrderStatus::Ready => [OrderStatus::OutForDelivery],
            OrderStatus::OutForDelivery => [OrderStatus::Delivered],
            default => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw new InvalidArgumentException("Transition {$from->value} → {$to->value} non autorisée.");
        }
    }
}
