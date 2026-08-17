<?php

namespace Tests\Feature;

use App\Enums\MenuCategory;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint6AccompanimentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dish_with_multiple_accompaniments_creates_order_items_with_extra_prices(): void
    {
        config(['synoria.payments.sandbox' => true]);

        $customer = User::factory()->create();

        $restaurant = Restaurant::factory()->create([
            'latitude' => 4.05,
            'longitude' => 11.53,
            'delivery_fee' => 500,
            'is_validated' => true,
        ]);

        $dish = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Poisson à la sauce',
            'description' => 'Plat',
            'price' => 5000,
            'category' => MenuCategory::Plats->value,
            'is_available' => true,
        ]);

        $acc1 = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Attiéké',
            'description' => 'Accompagnement inclus',
            'price' => 1000,
            'category' => MenuCategory::Accompagnements->value,
            'is_available' => true,
        ]);

        $acc2 = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Sauce piment',
            'description' => 'Accompagnement supplément',
            'price' => 1500,
            'category' => MenuCategory::Accompagnements->value,
            'is_available' => true,
        ]);

        $dish->accompanimentOptions()->sync([
            $acc1->id => ['extra_price' => 0, 'is_available' => true],
            $acc2->id => ['extra_price' => 300, 'is_available' => true],
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'menu_item_id' => $dish->id,
                'quantity' => 1,
                'accompaniment_ids' => [$acc1->id, $acc2->id],
            ])
            ->assertRedirect(route('cart.show'));

        $response = $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'delivery_address' => 'Bastos, Yaoundé',
                'delivery_phone' => '+237 6 00 00 00 00',
                'delivery_lat' => $restaurant->latitude,
                'delivery_lng' => $restaurant->longitude,
                'payment_method' => PaymentMethod::MtnMomo->value,
                'payment_phone' => '+237 6 00 00 00 00',
                'notes' => null,
            ]);

        $response->assertRedirect();

        $order = Order::query()->latest()->first();
        $this->assertNotNull($order);

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame($restaurant->id, $order->restaurant_id);

        $items = $order->items()->get();
        $this->assertCount(3, $items);

        $byName = $items->pluck('unit_price', 'name')->all();

        $this->assertSame(5000, (int) ($byName[$dish->name] ?? -1));
        $this->assertSame(0, (int) ($byName[$acc1->name] ?? -1));
        $this->assertSame(300, (int) ($byName[$acc2->name] ?? -1));
    }

    public function test_accompaniments_hidden_from_public_menu_but_drinks_visible(): void
    {
        $restaurant = Restaurant::factory()->create(['is_validated' => true, 'is_open' => true]);

        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Poisson braisé',
            'category' => MenuCategory::Plats->value,
            'is_available' => true,
        ]);

        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Attiéké',
            'category' => MenuCategory::Accompagnements->value,
            'is_available' => true,
        ]);

        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Coca-Cola',
            'category' => MenuCategory::Boissons->value,
            'is_available' => true,
        ]);

        $this->get(route('restaurants.show', $restaurant))
            ->assertOk()
            ->assertSee('Poisson braisé')
            ->assertSee('Coca-Cola')
            ->assertSee('Boissons')
            ->assertDontSee('Attiéké');
    }

    public function test_customer_can_order_drink_without_dish(): void
    {
        config(['synoria.payments.sandbox' => true]);

        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'latitude' => 4.05,
            'longitude' => 11.53,
            'delivery_fee' => 500,
            'is_validated' => true,
        ]);

        $drink = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Jus de bissap',
            'price' => 800,
            'category' => MenuCategory::Boissons->value,
            'is_available' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), [
                'menu_item_id' => $drink->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.show'));

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'delivery_address' => 'Bastos, Yaoundé',
                'delivery_phone' => '+237 6 00 00 00 00',
                'delivery_lat' => $restaurant->latitude,
                'delivery_lng' => $restaurant->longitude,
                'payment_method' => PaymentMethod::MtnMomo->value,
                'payment_phone' => '+237 6 00 00 00 00',
            ])
            ->assertRedirect();

        $order = Order::query()->latest()->first();
        $this->assertNotNull($order);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame('Jus de bissap', $order->items()->first()->name);
        $this->assertSame(800, (int) $order->items()->first()->unit_price);
    }
}

