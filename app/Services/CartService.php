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
     * @return array{restaurant_id: int|null, lines: array<string, array{menu_item_id: int|null, name: string, unit_price: int, quantity: int}>}
     */
    private function cart(): array
    {
        return Session::get(self::SESSION_KEY, [
            'restaurant_id' => null,
            'lines' => [],
        ]);
    }

    /** @param array{restaurant_id: int|null, lines: array<string, array{menu_item_id: int|null, name: string, unit_price: int, quantity: int}>} $cart */
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
            $cart = ['restaurant_id' => null, 'lines' => []];
        }

        $cart['restaurant_id'] = $menuItem->restaurant_id;
        $lineKey = $this->menuLineKey($menuItem->id);

        if (! isset($cart['lines'][$lineKey])) {
            $cart['lines'][$lineKey] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'unit_price' => (int) $menuItem->price,
                'quantity' => 0,
            ];
        }

        $cart['lines'][$lineKey]['quantity'] += $quantity;

        $this->save($cart);
    }

    /**
     * Add a dish plus its selected linked accompaniments.
     *
     * @param array<int, int|string> $accompanimentIds
     */
    public function addDishWithAccompaniments(MenuItem $dish, int $quantity, array $accompanimentIds): void
    {
        if (! $dish->is_available) {
            throw new InvalidArgumentException('Ce plat n’est pas disponible.');
        }

        $quantity = max(1, $quantity);
        $cart = $this->cart();

        if ($cart['restaurant_id'] !== null && $cart['restaurant_id'] !== $dish->restaurant_id) {
            $cart = ['restaurant_id' => null, 'lines' => []];
        }

        // If user re-adds the dish with a different accompaniment selection, we keep a single coherent set.
        $cart['lines'] = $this->removeDishLinesFromCart($cart['lines'], $dish->id);

        $cart['restaurant_id'] = $dish->restaurant_id;

        $dishLineKey = $this->dishLineKey($dish->id);
        $cart['lines'][$dishLineKey] = [
            'menu_item_id' => $dish->id,
            'name' => $dish->name,
            'unit_price' => (int) $dish->price,
            'quantity' => $quantity,
        ];

        foreach ($accompanimentIds as $accompId) {
            $accompId = (int) $accompId;
            if ($accompId <= 0) {
                continue;
            }

            $option = $dish->accompanimentOptions()
                ->where('menu_items.id', $accompId)
                ->first();

            if (! $option || ! $option->is_available) {
                throw new InvalidArgumentException('Accompagnement invalide pour ce plat.');
            }

            $extraPrice = (int) ($option->pivot->extra_price ?? 0);
            $cart['lines'][$this->accompLineKey($dish->id, $accompId)] = [
                'menu_item_id' => $accompId,
                'name' => $option->name,
                'unit_price' => $extraPrice,
                'quantity' => $quantity,
            ];
        }

        if ($cart['lines'] === []) {
            $cart['restaurant_id'] = null;
        }

        $this->save($cart);
    }

    public function updateQuantity(string $lineKey, int $quantity): void
    {
        $cart = $this->cart();

        if (! isset($cart['lines'][$lineKey])) {
            return;
        }

        if ($quantity < 0) {
            $quantity = 0;
        }

        if ($quantity === 0) {
            unset($cart['lines'][$lineKey]);

            // If it's the dish line, remove its tied accompaniment lines too.
            if (str_starts_with($lineKey, 'dish_')) {
                $dishId = (int) substr($lineKey, strlen('dish_'));
                $cart['lines'] = $this->removeDishLinesFromCart($cart['lines'], $dishId);
            }

            if ($cart['lines'] === []) {
                $cart['restaurant_id'] = null;
            }

            $this->save($cart);
            return;
        }

        $quantity = max(1, $quantity);

        // Keep accompaniment quantity in sync with the dish quantity.
        if (str_starts_with($lineKey, 'dish_')) {
            $dishId = (int) substr($lineKey, strlen('dish_'));
            foreach ($cart['lines'] as $key => &$line) {
                if (str_starts_with($key, 'acc_'.$dishId.'_')) {
                    $line['quantity'] = $quantity;
                }
            }
            unset($line);
        } elseif (str_starts_with($lineKey, 'acc_')) {
            // Format: acc_{dishId}_{accompId}
            $parts = explode('_', $lineKey);
            $dishId = isset($parts[1]) ? (int) $parts[1] : null;
            if ($dishId) {
                $dishKey = $this->dishLineKey($dishId);
                if (isset($cart['lines'][$dishKey])) {
                    $quantity = (int) $cart['lines'][$dishKey]['quantity'];
                }
            }
        }

        $cart['lines'][$lineKey]['quantity'] = $quantity;

        $this->save($cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function isEmpty(): bool
    {
        return $this->cart()['lines'] === [];
    }

    public function count(): int
    {
        return (int) collect($this->cart()['lines'])->sum(fn ($line) => (int) $line['quantity']);
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
     * @return Collection<int, array{line_key: string, menu_item: MenuItem, quantity: int, unit_price: int, line_total: int, name: string}>
     */
    public function lines(): Collection
    {
        $cart = $this->cart();

        if ($cart['lines'] === []) {
            return collect();
        }

        $menuItemIds = collect($cart['lines'])
            ->pluck('menu_item_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $menuItems = MenuItem::query()
            ->whereIn('id', $menuItemIds)
            ->where('is_available', true)
            ->get()
            ->keyBy('id');

        return collect($cart['lines'])
            ->map(function (array $line, string $lineKey) use ($menuItems) {
                $menuItem = $line['menu_item_id'] ? $menuItems->get($line['menu_item_id']) : null;

                if (! $menuItem) {
                    return null;
                }

                return [
                    'line_key' => $lineKey,
                    'menu_item' => $menuItem,
                    'name' => $line['name'],
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => (int) $line['unit_price'],
                    'line_total' => (int) $line['unit_price'] * (int) $line['quantity'],
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

        return app(DeliveryFeeCalculator::class)->forRestaurant(
            $restaurant,
            $deliveryLat,
            $deliveryLng,
            $this->subtotal()
        );
    }

    public function total(): int
    {
        return $this->subtotal() + $this->deliveryFee();
    }

    private function menuLineKey(int $menuItemId): string
    {
        return 'mi_'.$menuItemId;
    }

    private function dishLineKey(int $dishId): string
    {
        return 'dish_'.$dishId;
    }

    private function accompLineKey(int $dishId, int $accompId): string
    {
        return 'acc_'.$dishId.'_'.$accompId;
    }

    /**
     * @param array<string, array{menu_item_id: int|null, name: string, unit_price: int, quantity: int}> $lines
     * @return array<string, array{menu_item_id: int|null, name: string, unit_price: int, quantity: int}>
     */
    private function removeDishLinesFromCart(array $lines, int $dishId): array
    {
        $dishKey = $this->dishLineKey($dishId);
        $prefix = 'acc_'.$dishId.'_';

        foreach (array_keys($lines) as $key) {
            if ($key === $dishKey || str_starts_with($key, $prefix)) {
                unset($lines[$key]);
            }
        }

        return $lines;
    }
}
