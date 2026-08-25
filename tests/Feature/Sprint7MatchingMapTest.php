<?php

namespace Tests\Feature;

use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\RestaurantMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Sprint7MatchingMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_closer_restaurant_ranks_above_far_expensive_one(): void
    {
        $near = Restaurant::factory()->create([
            'name' => 'Chez Proche',
            'latitude' => 3.8700,
            'longitude' => 11.5167,
            'delivery_fee' => 300,
            'is_validated' => true,
            'is_open' => true,
            'rating' => 4.0,
        ]);
        $far = Restaurant::factory()->create([
            'name' => 'Chez Loin',
            'latitude' => 4.0500,
            'longitude' => 9.7000,
            'delivery_fee' => 2000,
            'is_validated' => true,
            'is_open' => true,
            'rating' => 4.0,
        ]);

        MenuItem::factory()->create([
            'restaurant_id' => $near->id,
            'category' => MenuCategory::Plats->value,
            'price' => 2500,
            'is_available' => true,
        ]);
        MenuItem::factory()->create([
            'restaurant_id' => $far->id,
            'category' => MenuCategory::Plats->value,
            'price' => 12000,
            'is_available' => true,
        ]);

        $ranked = app(RestaurantMatcher::class)->rank(
            Restaurant::query()->with('menuItems')->get(),
            3.8667,
            11.5167
        );

        $this->assertSame('Chez Proche', $ranked->first()->name);
        $this->assertTrue(
            $ranked->count() === 1 || $ranked->first()->match_score >= $ranked->last()->match_score
        );
        $this->assertFalse($ranked->contains(fn ($r) => $r->name === 'Chez Loin'));
    }

    public function test_catalog_with_location_uses_relevance_sort(): void
    {
        $near = Restaurant::factory()->create([
            'name' => 'Grill Bastos',
            'latitude' => 3.8920,
            'longitude' => 11.5140,
            'delivery_fee' => 200,
            'is_validated' => true,
            'is_open' => true,
        ]);
        Restaurant::factory()->create([
            'name' => 'Grill Douala',
            'latitude' => 4.0500,
            'longitude' => 9.7000,
            'delivery_fee' => 2500,
            'is_validated' => true,
            'is_open' => true,
        ]);
        MenuItem::factory()->create(['restaurant_id' => $near->id, 'is_available' => true, 'price' => 2000]);

        $far = Restaurant::query()->where('name', 'Grill Douala')->first();
        MenuItem::factory()->create(['restaurant_id' => $far->id, 'is_available' => true, 'price' => 9000]);

        $this->get(route('restaurants.index', [
            'lat' => 3.8920,
            'lng' => 11.5140,
        ]))
            ->assertOk()
            ->assertSee('Grill Bastos')
            ->assertSee('match');
    }

    public function test_custom_preferences_page_can_be_saved(): void
    {
        $this->post(route('restaurants.preferences.store'), [
            'pref_distance' => 1,
            'pref_price' => 4,
            'pref_fee' => 3,
            'pref_courier' => 1,
            'pref_rating' => 1,
        ])->assertRedirect(route('restaurants.index'));

        $this->get(route('restaurants.preferences'))
            ->assertOk()
            ->assertSee('Petit budget');
    }

    public function test_live_tracking_stops_after_delivery(): void
    {
        $customer = User::factory()->create();
        $courier = User::factory()->courier()->create();
        $restaurant = Restaurant::factory()->create([
            'latitude' => 3.8480,
            'longitude' => 11.5021,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'courier_id' => $courier->id,
            'status' => OrderStatus::OutForDelivery,
            'delivery_lat' => 3.8900,
            'delivery_lng' => 11.5200,
            'courier_lat' => 3.8700,
            'courier_lng' => 11.5100,
        ]);

        $this->actingAs($customer)
            ->getJson(route('orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('sharing_active', true)
            ->assertJsonPath('status', 'out_for_delivery');

        $this->actingAs($customer)
            ->postJson(route('orders.location', $order), [
                'lat' => 3.8910,
                'lng' => 11.5210,
            ])
            ->assertOk();

        $this->assertEqualsWithDelta(3.8910, (float) $order->fresh()->customer_lat, 0.0001);

        $this->actingAs($courier)
            ->post(route('courier.missions.deliver', $order))
            ->assertRedirect();

        $order->refresh();
        $this->assertSame(OrderStatus::Delivered, $order->status);
        $this->assertNull($order->courier_lat);
        $this->assertNull($order->customer_lat);

        $this->actingAs($customer)
            ->getJson(route('orders.tracking', $order))
            ->assertOk()
            ->assertJsonPath('sharing_active', false)
            ->assertJsonPath('courier_lat', null);

        $this->actingAs($customer)
            ->postJson(route('orders.location', $order), [
                'lat' => 3.8910,
                'lng' => 11.5210,
            ])
            ->assertStatus(422);
    }

    public function test_odza_prefers_bastos_over_melen_and_excludes_ambam(): void
    {
        $bastos = Restaurant::factory()->create([
            'name' => 'Chez Bastos',
            'address' => 'Yaounde, Bastos',
            'latitude' => null,
            'longitude' => null,
            'is_validated' => true,
            'is_open' => true,
            'delivery_fee' => 400,
            'rating' => 4.0,
        ]);
        $melen = Restaurant::factory()->create([
            'name' => 'Chez Melen',
            'address' => 'GP Melen Yaounde',
            'latitude' => null,
            'longitude' => null,
            'is_validated' => true,
            'is_open' => true,
            'delivery_fee' => 400,
            'rating' => 4.0,
        ]);
        $ambam = Restaurant::factory()->create([
            'name' => 'Chez Ambam',
            'address' => 'Ambam, Nkoumekeke',
            'latitude' => null,
            'longitude' => null,
            'is_validated' => true,
            'is_open' => true,
            'delivery_fee' => 400,
            'rating' => 4.5,
        ]);

        foreach ([$bastos, $melen, $ambam] as $r) {
            MenuItem::factory()->create([
                'restaurant_id' => $r->id,
                'category' => MenuCategory::Plats->value,
                'price' => 3000,
                'is_available' => true,
            ]);
        }

        $odza = app(\App\Services\CameroonPlaceGeocoder::class)->point('odza');
        $ranked = app(RestaurantMatcher::class)->rank(
            Restaurant::query()->with('menuItems')->get(),
            $odza['lat'],
            $odza['lng'],
            ['distance' => 4, 'price' => 1, 'fee' => 1, 'courier' => 1, 'rating' => 1],
        );

        $names = $ranked->pluck('name')->all();
        $this->assertContains('Chez Bastos', $names);
        $this->assertContains('Chez Melen', $names);
        $this->assertNotContains('Chez Ambam', $names);
        $this->assertTrue(
            (float) $ranked->firstWhere('name', 'Chez Bastos')->distance_km
            < (float) $ranked->firstWhere('name', 'Chez Melen')->distance_km
        );
        $this->assertSame('Chez Bastos', $ranked->first()->name);
    }
}
