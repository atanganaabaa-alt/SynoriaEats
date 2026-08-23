<?php

namespace Tests\Feature;

use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint9CompanionTest extends TestCase
{
    use RefreshDatabase;

    public function test_companion_page_is_available(): void
    {
        $this->get(route('companion.show'))
            ->assertOk()
            ->assertSee('Compagnon culinaire');
    }

    public function test_companion_suggests_dishes_within_budget(): void
    {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Chez Budget',
            'is_validated' => true,
            'is_open' => true,
            'delivery_fee' => 500,
            'prep_time_min' => 20,
            'prep_time_max' => 35,
        ]);

        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Riz sauce',
            'category' => MenuCategory::Plats->value,
            'price' => 2500,
            'is_available' => true,
        ]);
        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Menu VIP',
            'category' => MenuCategory::Plats->value,
            'price' => 12000,
            'is_available' => true,
        ]);

        $reply = $this->postJson(route('companion.message'), [
            'message' => 'J’ai 4000 FCFA',
            'restaurant_id' => $restaurant->id,
        ])
            ->assertOk()
            ->assertJsonPath('mode', 'local')
            ->json('reply');

        $this->assertStringContainsString('Riz sauce', $reply);
        $this->assertStringNotContainsString('Menu VIP', $reply);
    }

    public function test_companion_explains_active_order_wait(): void
    {
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'prep_time_min' => 15,
            'prep_time_max' => 25,
            'is_validated' => true,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::Preparing,
            'number' => 'SE-WAIT-001',
        ]);

        $reply = $this->actingAs($customer)
            ->postJson(route('companion.message'), [
                'message' => 'Combien de temps d’attente ?',
            ])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString($order->number, $reply);
        $this->assertStringContainsString('En préparation', $reply);
    }
}
