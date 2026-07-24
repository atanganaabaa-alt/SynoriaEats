<?php

namespace App\Services;

use App\Models\MenuItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

class CartService
{
    private const SESSION_KEY = 'synoria_cart';

    /**
     * @return array{restaurant_id: int|null, items: array<int, int>}
     */
    private function cart(): array
    {
        return Session::get(self::SESSION_KEY, [
            'restaurant_id' => null,
            'items' => [],
        ]);
    }

    /**
     * @param  array{restaurant_id: int|null, items: array<int, int>}  $cart
     */
    private function save(array $cart): void
    {
        Session::put(self::SESSION_KEY, $cart);
    }

    public function add(MenuItem $menuItem, int $quantity = 1): void
    {
        if (! $menuItem->is_available) {
            throw new InvalidArgumentException('Ce plat n’est pas disponible.');
        }

        $quantity = max(1, $quantity);
        $cart = $this->cart();

        if ($cart['restaurant_id'] !== null && $cart['restaurant_id'] !== $menuItem->restaurant_id) {
            $cart = ['restaurant_id' => null, 'items' => []];
        }

        $cart['restaurant_id'] = $menuItem->restaurant_id;
        $cart['items'][$menuItem->id] = ($cart['items'][$menuItem->id] ?? 0) + $quantity;

        $this->save($cart);
    }

    public function updateQuantity(int $menuItemId, int $quantity): void
    {
        $cart = $this->cart();

        if (! isset($cart['items'][$menuItemId])) {
            return;
        }

        if ($quantity <= 0) {
            unset($cart['items'][$menuItemId]);
        } else {
            $cart['items'][$menuItemId] = $quantity;
        }

        if ($cart['items'] === []) {
            $cart['restaurant_id'] = null;
        }

        $this->save($cart);
    }

    public function remove(int $menuItemId): void
    {
        $this->updateQuantity($menuItemId, 0);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->cart()['items'] === [];
    }

    public function count(): int
    {
        return (int) array_sum($this->cart()['items']);
    }

    public function restaurantId(): ?int
    {
        return $this->cart()['restaurant_id'];
    }

    public function restaurant(): ?\App\Models\Restaurant
    {
        $restaurantId = $this->restaurantId();

        if (! $restaurantId) {
            return null;
        }

        return \App\Models\Restaurant::query()->find($restaurantId);
    }

    /**
     * @return Collection<int, array{menu_item: MenuItem, quantity: int, line_total: int}>
     */
    public function lines(): Collection
    {
        $cart = $this->cart();

        if ($cart['items'] === []) {
            return collect();
        }

        $menuItems = MenuItem::query()
            ->whereIn('id', array_keys($cart['items']))
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        return collect($cart['items'])
            ->map(function (int $quantity, int $menuItemId) use ($menuItems) {
                $menuItem = $menuItems->get($menuItemId);

                if (! $menuItem) {
                    return null;
                }

                return [
                    'menu_item' => $menuItem,
                    'quantity' => $quantity,
                    'line_total' => $menuItem->price * $quantity,
                ];
            })
            ->filter()
            ->values();
    }

    public function subtotal(): int
    {
        return (int) $this->lines()->sum('line_total');
    }

    public function deliveryFee(?float $deliveryLat = null, ?float $deliveryLng = null): int
    {
        $restaurant = $this->restaurant();

        if (! $restaurant) {
            return 0;
        }

        return app(DeliveryFeeCalculator::class)->forRestaurant($restaurant, $deliveryLat, $deliveryLng);
    }

    public function total(): int
    {
        return $this->subtotal() + $this->deliveryFee();
    }
}
