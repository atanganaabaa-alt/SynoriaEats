<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class Sprint2OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_catalog_until_owner_adds_menu(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Mon Resto Vide',
            'is_open' => true,
        ]);

        $this->get(route('restaurants.index'))
            ->assertOk()
            ->assertSee('Aucun restaurant avec menu disponible');
    }

    public function test_full_sprint2_flow_from_menu_to_order_history(): void
    {
        Log::spy();
        config(['synoria.notifications.channels' => 'log']);

        $owner = User::factory()->restaurantOwner()->create(['phone' => '655111222']);
        $customer = User::factory()->create(['role' => UserRole::Customer, 'phone' => '655000333']);

        $this->actingAs($owner)
            ->post(route('owner.restaurants.store'), [
                'name' => 'Chez Marie',
                'address' => 'Akwa, Douala',
                'description' => 'Cuisine camerounaise',
                'category' => 'Africain',
                'delivery_fee' => 500,
                'is_open' => '1',
            ])
            ->assertRedirect();

        $restaurant = Restaurant::query()->where('owner_id', $owner->id)->firstOrFail();
        $restaurant->update(['is_validated' => true]);

        $this->actingAs($owner)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Poulet DG',
                'description' => 'Portion généreuse',
                'price' => 3500,
                'category' => 'Plats',
                'is_available' => '1',
            ])
            ->assertRedirect(route('owner.restaurants.show', $restaurant));

        $menuItem = MenuItem::query()->where('restaurant_id', $restaurant->id)->firstOrFail();

        $this->get(route('restaurants.index'))
            ->assertOk()
            ->assertSee('Chez Marie');

        $this->actingAs($customer)
            ->get(route('restaurants.show', $restaurant))
            ->assertOk()
            ->assertSee('Poulet DG');

        $this->actingAs($customer)
            ->post(route('cart.store'), ['menu_item_id' => $menuItem->id, 'quantity' => 2])
            ->assertRedirect(route('cart.show'));

        $this->actingAs($customer)
            ->post(route('checkout.store'), [
                'delivery_address' => 'Bonapriso, Douala',
                'delivery_phone' => '655000333',
                'payment_method' => 'orange_money',
            ])
            ->assertRedirect();

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame(7500, $order->total);

        Log::shouldHaveReceived('info')->atLeast()->once();

        $this->actingAs($customer)
            ->get(route('orders.index'))
            ->assertOk()
            ->assertSee($order->number);

        $this->actingAs($owner)
            ->get(route('owner.orders.index'))
            ->assertOk()
            ->assertSee($order->number);

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'accepted'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'preparing'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->patch(route('owner.orders.update', $order), ['status' => 'ready'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);
    }
}
