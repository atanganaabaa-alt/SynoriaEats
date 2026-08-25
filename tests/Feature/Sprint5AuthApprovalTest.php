<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Sprint5AuthApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_register_does_not_offer_courier_role(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Client')
            ->assertSee('Restaurateur')
            ->assertDontSee('value="courier"', false);
    }

    public function test_cannot_register_as_courier(): void
    {
        $this->post(route('register'), [
            'name' => 'Livreur Pirate',
            'email' => 'pirate@example.com',
            'role' => UserRole::Courier->value,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'pirate@example.com']);
    }

    public function test_restaurant_owner_registers_with_documents_and_stays_pending(): void
    {
        Storage::fake('public');

        $this->post(route('register'), [
            'name' => 'Amina',
            'email' => 'amina@example.com',
            'role' => UserRole::RestaurantOwner->value,
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'restaurant_name' => 'Chez Amina',
            'restaurant_address' => 'Bastos, Yaoundé',
            'commerce_register' => UploadedFile::fake()->image('rccm.jpg'),
            'identity' => UploadedFile::fake()->image('cni.jpg'),
        ])->assertRedirect(route('dashboard', absolute: false));

        $owner = User::query()->where('email', 'amina@example.com')->first();
        $this->assertNotNull($owner);
        $this->assertSame(ApprovalStatus::Pending, $owner->approval_status);

        $restaurant = Restaurant::query()->where('name', 'Chez Amina')->first();
        $this->assertNotNull($restaurant);
        $this->assertSame(ApprovalStatus::Pending, $restaurant->status);
        $this->assertFalse($restaurant->is_validated);
        $this->assertGreaterThanOrEqual(2, $restaurant->documents()->count());

        $this->actingAs($owner)
            ->get(route('owner.restaurants.index'))
            ->assertRedirect(route('owner.pending'));

        $this->actingAs($owner)
            ->get(route('owner.menu-items.create', $restaurant))
            ->assertRedirect(route('owner.pending'));
    }

    public function test_admin_can_approve_restaurant_then_owner_can_add_menu(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $owner = User::factory()->restaurantOwner()->create([
            'approval_status' => ApprovalStatus::Pending,
            'approved_at' => null,
        ]);
        $restaurant = Restaurant::factory()->pending()->create(['owner_id' => $owner->id]);
        $restaurant->documents()->create([
            'type' => \App\Enums\DocumentType::CommerceRegister,
            'url' => 'restaurant-docs/rccm.pdf',
            'original_name' => 'rccm.pdf',
        ]);
        $restaurant->documents()->create([
            'type' => \App\Enums\DocumentType::Identity,
            'url' => 'restaurant-docs/cni.jpg',
            'original_name' => 'cni.jpg',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.restaurants.update', $restaurant), [
                'decision' => 'approved',
                'notes' => 'Dossier OK',
                'checks' => ['docs_readable', 'identity_ok', 'address_ok', 'commerce_ok'],
            ])
            ->assertRedirect();

        $restaurant->refresh();
        $owner->refresh();
        $this->assertTrue($restaurant->is_validated);
        $this->assertSame(ApprovalStatus::Approved, $restaurant->status);
        $this->assertSame(ApprovalStatus::Approved, $owner->approval_status);

        $this->actingAs($owner)
            ->get(route('owner.restaurants.show', $restaurant))
            ->assertOk();

        $this->actingAs($owner)
            ->get(route('owner.pending'))
            ->assertRedirect(route('owner.restaurants.index'));

        $this->actingAs($owner)
            ->get(route('owner.restaurants.index'))
            ->assertOk()
            ->assertSee($restaurant->name)
            ->assertSee('Approuvé');
    }

    public function test_additional_restaurant_stays_pending_and_closed(): void
    {
        $owner = User::factory()->restaurantOwner()->create([
            'approval_status' => ApprovalStatus::Approved,
            'approved_at' => now(),
        ]);
        Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'status' => ApprovalStatus::Approved,
            'is_validated' => true,
            'is_open' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('owner.restaurants.store'), [
                'name' => 'Deuxième Adresse',
                'address' => 'Melen, Yaoundé',
                'is_open' => '1',
            ])
            ->assertRedirect();

        $second = Restaurant::query()->where('name', 'Deuxième Adresse')->first();
        $this->assertNotNull($second);
        $this->assertSame(ApprovalStatus::Pending, $second->status);
        $this->assertFalse($second->is_validated);
        $this->assertFalse($second->is_open);
    }

    public function test_unapproved_courier_cannot_claim_missions(): void
    {
        $courier = User::factory()->courier()->create([
            'approval_status' => ApprovalStatus::Pending,
            'approved_at' => null,
        ]);

        $this->actingAs($courier)
            ->get(route('courier.missions.index'))
            ->assertRedirect(route('courier.pending'));
    }

    public function test_admin_creates_and_approves_partner_courier(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.couriers.store'), [
                'name' => 'Paul Livreur',
                'email' => 'paul@partner.test',
                'partner_name' => 'Yango',
                'password' => 'Password1!',
            ])
            ->assertRedirect();

        $courier = User::query()->where('email', 'paul@partner.test')->first();
        $this->assertNotNull($courier);
        $this->assertSame(UserRole::Courier, $courier->role);
        $this->assertSame(ApprovalStatus::Pending, $courier->approval_status);

        $this->actingAs($admin)
            ->patch(route('admin.couriers.update', $courier), [
                'decision' => 'approved',
            ])
            ->assertRedirect();

        $this->assertSame(ApprovalStatus::Approved, $courier->fresh()->approval_status);
    }

    public function test_pending_restaurant_stays_hidden_from_catalog(): void
    {
        $restaurant = Restaurant::factory()->pending()->create(['name' => 'Secret Grill']);
        MenuItem::factory()->create(['restaurant_id' => $restaurant->id, 'is_available' => true]);

        $this->get(route('restaurants.index'))
            ->assertOk()
            ->assertDontSee('Secret Grill');
    }

    public function test_api_cannot_register_courier(): void
    {
        $this->postJson('/api/register', [
            'name' => 'API Courier',
            'email' => 'api-courier@example.com',
            'role' => 'courier',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertUnprocessable();
    }
}
