<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\DeliveryFeeCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint3DeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_can_claim_pickup_and_deliver(): void
    {
        $owner = User::factory()->restaurantOwner()->create(['phone' => '655111000']);
        $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '655222000']);
        $courier = User::factory()->courier()->create(['phone' => '655333000']);
        $restaurant = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'is_open' => true,
            'delivery_fee' => 500,
            'latitude' => 3.8480,
            'longitude' => 11.5021,
        ]);
        $item = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'price' => 2000,
            'is_available' => true,
        ]);

        $this->actingAs($customer)->post(route('cart.store'), ['menu_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Bastos, Yaoundé',
            'delivery_phone' => '655222000',
            'delivery_lat' => 3.8900,
            'delivery_lng' => 11.5200,
            'payment_method' => 'mtn_momo',
        ])->assertRedirect();

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertNotNull($order->delivery_lat);
        $this->assertGreaterThan(500, $order->delivery_fee);

        foreach (['accepted', 'preparing', 'ready'] as $status) {
            $this->actingAs($owner)
                ->patch(route('owner.orders.update', $order), ['status' => $status])
                ->assertRedirect();
        }

        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        $this->actingAs($courier)
            ->post(route('courier.missions.claim', $order))
            ->assertRedirect(route('courier.missions.show', $order));

        $this->assertSame($courier->id, $order->fresh()->courier_id);

        $this->actingAs($courier)
            ->post(route('courier.missions.pickup', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);

        $this->actingAs($courier)
            ->postJson(route('courier.missions.location', $order), [
                'lat' => 3.8700,
                'lng' => 11.5100,
            ])
            ->assertOk();

        $this->assertEqualsWithDelta(3.8700, (float) $order->fresh()->courier_lat, 0.0001);

        $this->actingAs($customer)
            ->getJson(route('orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('status', 'out_for_delivery')
            ->assertJsonPath('courier.name', $courier->name);

        $this->actingAs($courier)
            ->post(route('courier.missions.deliver', $order))
            ->assertRedirect(route('courier.missions.index'));

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertSame(1, $courier->fresh()->delivery_count);

        $this->actingAs($customer)
            ->post(route('orders.reviews.store', $order), [
                'restaurant_rating' => 5,
                'courier_rating' => 4,
                'comment' => 'Rapide et bon',
            ])
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('reviews', [
            'order_id' => $order->id,
            'restaurant_rating' => 5,
            'courier_rating' => 4,
        ]);

        $this->assertSame(5.0, (float) $restaurant->fresh()->rating);
        $this->assertSame(4.0, (float) $courier->fresh()->rating);
    }

    public function test_delivery_fee_calculator_uses_distance(): void
    {
        $calculator = new DeliveryFeeCalculator;
        $restaurant = Restaurant::factory()->make([
            'delivery_fee' => 500,
            'latitude' => 3.8480,
            'longitude' => 11.5021,
        ]);

        $at = Carbon::parse('2026-01-15 10:00:00');
        $fee = $calculator->forRestaurant($restaurant, 3.8900, 11.5200, 0, $at);

        $this->assertGreaterThan(500, $fee);
        $this->assertSame(500, $calculator->forRestaurant($restaurant, null, null, 0, $at));

        $quote = $calculator->quote($restaurant, 3.8900, 11.5200, 1500, $at);
        $this->assertArrayHasKey('breakdown', $quote);
    }

    public function test_customer_cannot_claim_mission(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);
        $order = Order::factory()->create([
            'restaurant_id' => $restaurant->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::Ready,
            'courier_id' => null,
        ]);

        $this->actingAs($customer)
            ->post(route('courier.missions.claim', $order))
            ->assertForbidden();
    }
}
