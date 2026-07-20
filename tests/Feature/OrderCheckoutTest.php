<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_add_to_cart_checkout_and_pay(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id, 'is_open' => true, 'delivery_fee' => 500]);
        $item = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'price' => 2500,
            'is_available' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), ['menu_item_id' => $item->id, 'quantity' => 2])
            ->assertRedirect();

        $response = $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Rue 1, Yaoundé',
            'delivery_phone' => '+237655000000',
            'payment_method' => 'orange_money',
            'payment_phone' => '+237655000000',
        ]);

        $order = $customer->orders()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(5500, $order->total);
        $this->assertCount(1, $order->items);
    }

    public function test_restaurant_owner_can_accept_order(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id, 'is_open' => true]);
        $item = MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'price' => 1000]);

        $this->actingAs($customer)->post(route('cart.store'), ['menu_item_id' => $item->id]);
        $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Adresse',
            'delivery_phone' => '+237600000000',
            'payment_method' => 'mtn_momo',
        ]);

        $order = $customer->orders()->first();

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
    }
}
