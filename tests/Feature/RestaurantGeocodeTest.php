<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\RestaurantGeocoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RestaurantGeocodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_endpoint_returns_coordinates_for_owner(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '3.8920000',
                    'lon' => '11.5140000',
                    'display_name' => 'Bastos, Yaoundé, Cameroun',
                ],
            ], 200),
        ]);

        $owner = User::factory()->create(['role' => UserRole::RestaurantOwner]);

        $this->actingAs($owner)
            ->postJson(route('owner.geocode'), [
                'address' => 'Bastos Yaoundé',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('lat', 3.892)
            ->assertJsonPath('lng', 11.514)
            ->assertJsonPath('source', 'nominatim');
    }

    public function test_geocoder_falls_back_to_local_places_when_nominatim_empty(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $place = app(RestaurantGeocoder::class)->geocode('Ambam, Nkoumekeke');

        $this->assertNotNull($place);
        $this->assertSame('local', $place['source']);
        $this->assertEqualsWithDelta(2.39, $place['lat'], 0.05);
    }

    public function test_creating_restaurant_persists_geocoded_coordinates(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                [
                    'lat' => '3.8660000',
                    'lon' => '11.4920000',
                    'display_name' => 'Melen, Yaoundé',
                ],
            ], 200),
        ]);

        $owner = User::factory()->create([
            'role' => UserRole::RestaurantOwner,
            'approval_status' => \App\Enums\ApprovalStatus::Approved,
        ]);

        $this->actingAs($owner)
            ->post(route('owner.restaurants.store'), [
                'name' => 'Resto Melen GPS',
                'address' => 'GP Melen Yaounde',
                'prep_time_min' => 20,
                'prep_time_max' => 35,
                'delivery_fee' => 500,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('restaurants', [
            'name' => 'Resto Melen GPS',
            'latitude' => 3.8660000,
            'longitude' => 11.4920000,
        ]);
    }
}
