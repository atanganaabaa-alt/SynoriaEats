<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_owner_can_add_menu_item(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Poulet DG',
                'description' => 'Classique',
                'price' => 4500,
                'category' => 'Plats',
                'is_available' => '1',
            ])
            ->assertRedirect(route('owner.restaurants.show', $restaurant))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Poulet DG',
            'price' => 4500,
        ]);
    }

    public function test_customer_cannot_add_menu_item(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($customer)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Hack',
                'price' => 1000,
            ])
            ->assertForbidden();
    }
}
