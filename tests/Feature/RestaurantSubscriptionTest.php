<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\MenuCategory;
use App\Enums\SubscriptionPlan;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_starts_thirty_day_trial(): void
    {
        $owner = User::factory()->restaurantOwner()->create([
            'approval_status' => ApprovalStatus::Pending,
        ]);
        $restaurant = Restaurant::factory()->pending()->create(['owner_id' => $owner->id]);

        app(ApprovalService::class)->approveRestaurant($restaurant);

        $restaurant->refresh();
        $this->assertTrue($restaurant->isApproved());
        $this->assertTrue($restaurant->onTrial());
        $this->assertTrue($restaurant->hasCatalogAccess());
        $this->assertEquals(
            now()->addDays(30)->toDateString(),
            $restaurant->trial_ends_at->toDateString()
        );
    }

    public function test_expired_restaurant_hidden_from_catalog(): void
    {
        $visible = Restaurant::factory()->create(['name' => 'Visible Kitchen', 'is_open' => true]);
        MenuItem::factory()->create([
            'restaurant_id' => $visible->id,
            'category' => MenuCategory::Plats,
            'is_available' => true,
        ]);

        $expired = Restaurant::factory()->expired()->create([
            'name' => 'Expired Kitchen',
            'is_open' => true,
        ]);
        MenuItem::factory()->create([
            'restaurant_id' => $expired->id,
            'category' => MenuCategory::Plats,
            'is_available' => true,
        ]);

        $this->get(route('restaurants.index'))
            ->assertOk()
            ->assertSee('Visible Kitchen')
            ->assertDontSee('Expired Kitchen');
    }

    public function test_owner_can_activate_essentiel_sandbox_subscription(): void
    {
        $owner = User::factory()->restaurantOwner()->create([
            'approval_status' => ApprovalStatus::Approved,
            'role' => UserRole::RestaurantOwner,
        ]);
        $restaurant = Restaurant::factory()->expired()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('owner.restaurants.subscription', $restaurant))
            ->assertOk()
            ->assertSee('15 000')
            ->assertSee('25 000')
            ->assertSee('FCFA / mois');

        $this->actingAs($owner)
            ->post(route('owner.restaurants.subscription.store', $restaurant), [
                'plan' => SubscriptionPlan::Essentiel->value,
            ])
            ->assertRedirect(route('owner.restaurants.subscription', $restaurant));

        $restaurant->refresh();
        $this->assertTrue($restaurant->hasPaidSubscription());
        $this->assertSame(SubscriptionPlan::Essentiel, $restaurant->subscription_plan);
        $this->assertTrue($restaurant->subscription_ends_at->greaterThan(now()->addDays(27)));
        $this->assertTrue($restaurant->subscription_ends_at->lessThan(now()->addDays(35)));
    }

    public function test_cannot_open_catalog_without_active_access(): void
    {
        $owner = User::factory()->restaurantOwner()->create([
            'approval_status' => ApprovalStatus::Approved,
            'role' => UserRole::RestaurantOwner,
        ]);
        $restaurant = Restaurant::factory()->expired()->create([
            'owner_id' => $owner->id,
            'is_open' => false,
        ]);

        $this->actingAs($owner)
            ->put(route('owner.restaurants.update', $restaurant), [
                'name' => $restaurant->name,
                'address' => $restaurant->address,
                'is_open' => '1',
            ])
            ->assertRedirect(route('owner.restaurants.subscription', $restaurant));

        $this->assertFalse($restaurant->fresh()->is_open);
    }

    public function test_pro_restaurant_is_listed_before_a_better_rated_essentiel(): void
    {
        $pro = Restaurant::factory()->create([
            'name' => 'Pro Kitchen',
            'rating' => 3.2,
            'is_open' => true,
            'subscription_plan' => SubscriptionPlan::Pro,
            'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => now()->subDay(),
        ]);
        $basic = Restaurant::factory()->create([
            'name' => 'Essentiel Kitchen',
            'rating' => 4.9,
            'is_open' => true,
            'subscription_plan' => SubscriptionPlan::Essentiel,
            'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => now()->subDay(),
        ]);
        MenuItem::factory()->create(['restaurant_id' => $pro->id, 'is_available' => true]);
        MenuItem::factory()->create(['restaurant_id' => $basic->id, 'is_available' => true]);

        $this->get(route('restaurants.index'))
            ->assertOk()
            ->assertSeeInOrder(['Pro Kitchen', 'Essentiel Kitchen']);
    }

    public function test_pro_order_commission_is_eight_percent_of_food(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'is_open' => true,
            'delivery_fee' => 500,
            'subscription_plan' => SubscriptionPlan::Pro,
            'subscription_ends_at' => now()->addMonth(),
            'trial_ends_at' => now()->subDay(),
        ]);
        $item = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'price' => 2500,
            'is_available' => true,
        ]);

        $this->travelTo(now()->setTime(15, 0));

        $this->actingAs($customer)->post(route('cart.store'), [
            'menu_item_id' => $item->id,
            'quantity' => 2,
        ]);
        $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Rue 1, Yaoundé',
            'delivery_phone' => '+237655000000',
            'payment_method' => 'orange_money',
            'payment_phone' => '+237655000000',
        ]);

        $order = $customer->orders()->first();
        $this->assertNotNull($order);
        $this->assertSame(5000, $order->subtotal);
        $this->assertSame(400, $order->commission);
    }
}
