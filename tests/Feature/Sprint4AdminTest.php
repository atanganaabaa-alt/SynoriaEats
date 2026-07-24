<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint4AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id, 'is_validated' => true]);

        Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'payment_status' => PaymentStatus::Paid,
            'total' => 5000,
            'commission' => 500,
            'subtotal' => 4500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard admin')
            ->assertSee('5 000');
    }

    public function test_admin_can_suspend_user_and_validate_restaurant(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create(['is_active' => true]);
        $owner = User::factory()->restaurantOwner()->create();
        $restaurant = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'is_validated' => false,
            'is_open' => true,
        ]);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'is_available' => true]);

        $this->get(route('restaurants.index'))->assertOk()->assertDontSee($restaurant->name);

        $this->actingAs($admin)
            ->patch(route('admin.restaurants.update', $restaurant), ['is_validated' => '1'])
            ->assertRedirect();

        $this->assertTrue($restaurant->fresh()->is_validated);
        $this->get(route('restaurants.index'))->assertOk()->assertSee($restaurant->name);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $customer), ['is_active' => '0'])
            ->assertRedirect();

        $this->assertFalse($customer->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_admin_area(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_new_restaurant_awaits_validation(): void
    {
        $owner = User::factory()->restaurantOwner()->create();

        $this->actingAs($owner)
            ->post(route('owner.restaurants.store'), [
                'name' => 'Nouveau Spot',
                'address' => 'Douala',
                'is_open' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Nouveau Spot',
            'is_validated' => false,
        ]);
    }

    public function test_catalog_filters_by_min_rating_and_fee(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $cheap = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Cheap Eats',
            'rating' => 4.8,
            'delivery_fee' => 0,
            'is_validated' => true,
            'is_open' => true,
        ]);
        $pricey = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Pricey Place',
            'rating' => 3.0,
            'delivery_fee' => 2000,
            'is_validated' => true,
            'is_open' => true,
        ]);
        MenuItem::factory()->create(['restaurant_id' => $cheap->id, 'is_available' => true]);
        MenuItem::factory()->create(['restaurant_id' => $pricey->id, 'is_available' => true]);

        $this->get(route('restaurants.index', ['min_rating' => 4, 'max_fee' => 500]))
            ->assertOk()
            ->assertSee('Cheap Eats')
            ->assertDontSee('Pricey Place');
    }

    public function test_admin_commissions_page(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create([
            'payment_status' => PaymentStatus::Paid,
            'commission' => 750,
            'total' => 7500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.commissions.index'))
            ->assertOk()
            ->assertSee($order->number)
            ->assertSee('750');
    }
}
