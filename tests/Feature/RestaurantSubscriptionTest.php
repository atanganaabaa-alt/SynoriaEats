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
            ->post(route('owner.restaurants.subscription.store', $restaurant), [
                'plan' => SubscriptionPlan::Essentiel->value,
            ])
            ->assertRedirect(route('owner.restaurants.subscription', $restaurant));

        $restaurant->refresh();
        $this->assertTrue($restaurant->hasPaidSubscription());
        $this->assertSame(SubscriptionPlan::Essentiel, $restaurant->subscription_plan);
        $this->assertTrue($restaurant->subscription_ends_at->greaterThan(now()->addMonths(11)));
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
}
