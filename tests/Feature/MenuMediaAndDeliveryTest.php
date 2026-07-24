<?php

namespace Tests\Feature;

use App\Enums\MenuCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use App\Notifications\OrderLiveUpdate;
use App\Services\CloudinaryUploader;
use App\Services\DeliveryFeeCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuMediaAndDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_drink_and_side(): void
    {
        $owner = User::factory()->restaurantOwner()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Jus de bissap',
                'price' => 500,
                'category' => MenuCategory::Boissons->value,
                'is_available' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Alloco',
                'price' => 1000,
                'category' => MenuCategory::Accompagnements->value,
                'is_available' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Jus de bissap',
            'category' => 'Boissons',
        ]);
        $this->assertDatabaseHas('menu_items', [
            'name' => 'Alloco',
            'category' => 'Accompagnements',
        ]);
    }

    public function test_photo_upload_falls_back_to_local_without_cloudinary(): void
    {
        Storage::fake('public');
        $owner = User::factory()->restaurantOwner()->create();
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('owner.menu-items.store', $restaurant), [
                'name' => 'Poulet',
                'price' => 3500,
                'category' => MenuCategory::Plats->value,
                'photo' => UploadedFile::fake()->image('poulet.jpg'),
                'is_available' => '1',
            ])
            ->assertRedirect();

        $item = MenuItem::query()->firstOrFail();
        $this->assertNotNull($item->photo_url);
        $this->assertFalse(str_starts_with($item->photo_url, 'http'));
        Storage::disk('public')->assertExists($item->photo_url);
    }

    public function test_delivery_quote_includes_distance_and_factors(): void
    {
        $calculator = new DeliveryFeeCalculator;
        $restaurant = Restaurant::factory()->make([
            'delivery_fee' => 500,
            'latitude' => 3.8480,
            'longitude' => 11.5021,
        ]);

        $quote = $calculator->quote($restaurant, 3.8900, 11.5200, 2000, Carbon::parse('2026-01-15 10:00:00'));

        $this->assertGreaterThan(500, $quote['fee']);
        $this->assertNotNull($quote['distance_km']);
        $this->assertArrayHasKey('base', $quote['breakdown']);
        $this->assertArrayHasKey('distance', $quote['breakdown']);
        $this->assertArrayHasKey('small_order', $quote['breakdown']);
    }

    public function test_live_notifications_poll_returns_order_updates(): void
    {
        $customer = User::factory()->create();
        $order = \App\Models\Order::factory()->create(['customer_id' => $customer->id]);

        $customer->notify(new OrderLiveUpdate($order, 'Test', 'Body'));

        $this->actingAs($customer)
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('notifications.0.title', 'Test');

        $this->assertCount(0, $customer->fresh()->unreadNotifications);
    }

    public function test_cloudinary_uploader_reports_when_not_configured(): void
    {
        config([
            'services.cloudinary.cloud_name' => null,
            'services.cloudinary.key' => null,
            'services.cloudinary.secret' => null,
            'services.cloudinary.upload_preset' => null,
        ]);

        $this->assertFalse(app(CloudinaryUploader::class)->isConfigured());
    }
}
