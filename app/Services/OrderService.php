<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\User;
use App\Services\Notifications\Notifier;
use App\Services\Payments\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private PaymentManager $payments,
        private DeliveryFeeCalculator $deliveryFees,
        private Notifier $notifier,
    ) {}

    /**
     * @param  array{delivery_address: string, delivery_phone: string, payment_method: string, payment_phone?: string|null, notes?: string|null, delivery_lat?: float|null, delivery_lng?: float|null}  $data
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

        $restaurant = $cart->restaurant();

        if (! $restaurant) {
            throw new InvalidArgumentException('Restaurant introuvable pour ce panier.');
        }

        $deliveryLat = isset($data['delivery_lat']) ? (float) $data['delivery_lat'] : null;
        $deliveryLng = isset($data['delivery_lng']) ? (float) $data['delivery_lng'] : null;
        $subtotal = $cart->subtotal();
        $deliveryFee = $this->deliveryFees->forRestaurant($restaurant, $deliveryLat, $deliveryLng, $subtotal);
        $commission = (int) round($subtotal * config('synoria.commission_rate', 0.10));
        $total = $subtotal + $deliveryFee;
        $paymentMethod = PaymentMethod::from($data['payment_method']);
        $paymentPhone = $data['payment_phone'] ?? $data['delivery_phone'];

        $order = DB::transaction(function () use ($customer, $cart, $data, $lines, $subtotal, $deliveryFee, $commission, $total, $paymentMethod, $deliveryLat, $deliveryLng) {
            $order = Order::query()->create([
                'number' => $this->generateNumber(),
                'customer_id' => $customer->id,
                'restaurant_id' => $cart->restaurantId(),
                'delivery_address' => $data['delivery_address'],
                'delivery_phone' => $data['delivery_phone'],
                'delivery_lat' => $deliveryLat,
                'delivery_lng' => $deliveryLng,
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

        OrderStatusChanged::dispatch($order->fresh(['courier', 'restaurant.owner']), $previous);

        return $order->fresh();
    }

    public function claim(Order $order, User $courier): Order
    {
        abort_unless($courier->isCourier() || $courier->isAdmin(), 403);

        $claimed = DB::transaction(function () use ($order, $courier) {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== OrderStatus::Ready || $locked->courier_id !== null) {
                throw new InvalidArgumentException('Cette mission n’est plus disponible.');
            }

            $locked->update(['courier_id' => $courier->id]);

            return $locked->fresh(['restaurant', 'customer', 'courier']);
        });

        $this->notifier->send(
            $claimed->delivery_phone,
            sprintf('SynoriaEats : un livreur a pris ta commande %s.', $claimed->number)
        );

        if ($courier->phone) {
            $this->notifier->send(
                $courier->phone,
                sprintf(
                    'SynoriaEats : mission %s acceptée — %s, %s.',
                    $claimed->number,
                    $claimed->restaurant->name,
                    $claimed->restaurant->address
                )
            );
        }

        return $claimed;
    }

    public function startDelivery(Order $order, User $courier): Order
    {
        $this->assertAssignedCourier($order, $courier);
        $this->assertValidTransition($order->status, OrderStatus::OutForDelivery);

        $previous = $order->status;
        $order->update(['status' => OrderStatus::OutForDelivery]);

        OrderStatusChanged::dispatch($order->fresh(['courier', 'restaurant.owner']), $previous);

        return $order->fresh();
    }

    public function completeDelivery(Order $order, User $courier): Order
    {
        $this->assertAssignedCourier($order, $courier);
        $this->assertValidTransition($order->status, OrderStatus::Delivered);

        $previous = $order->status;

        DB::transaction(function () use ($order, $courier) {
            $order->update([
                'status' => OrderStatus::Delivered,
                'delivered_at' => now(),
            ]);

            User::query()->whereKey($courier->id)->increment('delivery_count');
        });

        OrderStatusChanged::dispatch($order->fresh(['courier', 'restaurant.owner']), $previous);

        return $order->fresh();
    }

    public function updateCourierLocation(Order $order, User $courier, float $lat, float $lng): Order
    {
        $this->assertAssignedCourier($order, $courier);

        if (! in_array($order->status, [OrderStatus::Ready, OrderStatus::OutForDelivery], true)) {
            throw new InvalidArgumentException('Position non mise à jour pour ce statut.');
        }

        $order->update([
            'courier_lat' => $lat,
            'courier_lng' => $lng,
        ]);

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

    private function assertAssignedCourier(Order $order, User $courier): void
    {
        if ($courier->isAdmin()) {
            return;
        }

        abort_unless(
            $courier->isCourier() && $order->courier_id === $courier->id,
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
