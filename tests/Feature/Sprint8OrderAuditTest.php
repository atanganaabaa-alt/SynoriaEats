<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint8OrderAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_changes_are_timestamped_and_visible_to_customer(): void
    {
        [$owner, $customer, $order] = $this->paidOrder();

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('order_status_events', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::Accepted->value,
            'actor_id' => $owner->id,
        ]);

        $this->actingAs($customer)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Suivi de préparation')
            ->assertSee('Acceptée');

        $this->actingAs($customer)
            ->getJson(route('orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('status', 'accepted')
            ->assertJsonCount(2, 'timeline');
    }

    public function test_notifications_are_audited_even_when_a_channel_fails(): void
    {
        config(['synoria.notifications.channels' => 'log']);

        [$owner, $customer, $order] = $this->paidOrder();

        $this->assertTrue(
            NotificationDelivery::query()->where('order_id', $order->id)->where('status', 'sent')->exists()
        );

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);
        $this->assertGreaterThan(
            0,
            NotificationDelivery::query()->where('order_id', $order->id)->count()
        );
    }

    public function test_admin_can_audit_order_timeline_and_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        [, $customer, $order] = $this->paidOrder();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->number);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Suivi de préparation')
            ->assertSee('Notifications envoyées')
            ->assertSee($customer->name);
    }

    /**
     * @return array{0: User, 1: User, 2: Order}
     */
    private function paidOrder(): array
    {
        $owner = User::factory()->restaurantOwner()->create(['phone' => '655111000']);
        $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '655222000']);
        $restaurant = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'is_open' => true,
            'is_validated' => true,
        ]);
        $item = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'price' => 2500,
            'is_available' => true,
        ]);

        $this->actingAs($customer)->post(route('cart.store'), ['menu_item_id' => $item->id, 'quantity' => 1]);
        $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Bastos, Yaoundé',
            'delivery_phone' => '655222000',
            'payment_method' => 'mtn_momo',
        ])->assertRedirect();

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();

        return [$owner, $customer, $order];
    }
}
