<?php

namespace Tests\Feature;

use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Models\CompanionMessage;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Sprint9CompanionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synoria.companion.enabled' => true,
            'synoria.companion.provider' => 'local',
            'synoria.companion.name' => 'Sara',
        ]);
    }

    public function test_local_agent_holds_conversation_and_persists_history(): void
    {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Grill Bastos',
            'is_validated' => true,
            'is_open' => true,
        ]);
        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Poulet DG',
            'category' => MenuCategory::Plats->value,
            'price' => 3500,
            'is_available' => true,
        ]);

        $customer = User::factory()->create();

        $hi = $this->actingAs($customer)
            ->postJson(route('companion.message'), [
                'message' => 'wassup tu me parle pas ?',
            ])
            ->assertOk()
            ->assertJsonPath('mode', 'local')
            ->assertJsonPath('agent', 'Sara')
            ->json('reply');

        $this->assertStringContainsString('Sara', $hi);
        $this->assertStringNotContainsString('—', $hi);

        $followUp = $this->actingAs($customer)
            ->postJson(route('companion.message'), [
                'message' => 'J’ai 5000 FCFA, quelque chose de local et copieux',
                'restaurant_id' => $restaurant->id,
            ])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString('Poulet DG', $followUp);
        $this->assertTrue(
            str_contains($followUp, '5 000') || str_contains($followUp, '5000'),
            'Expected budget mention in reply'
        );

        $this->assertDatabaseCount('companion_messages', 4);

        $history = $this->actingAs($customer)
            ->getJson(route('sara.history'))
            ->assertOk()
            ->json('history');

        $this->assertCount(4, $history);

        $this->assertDatabaseHas('user_preferences', ['user_id' => $customer->id]);
        $pref = UserPreference::query()->where('user_id', $customer->id)->first();
        $this->assertSame(5000, (int) ($pref->tastes['budget_moyen'] ?? 0));
    }

    public function test_sara_learns_aversions_from_user_message(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->postJson(route('sara.message'), [
                'message' => 'Je n’aime plus le poisson',
            ])
            ->assertOk();

        $pref = UserPreference::query()->where('user_id', $customer->id)->first();
        $this->assertNotNull($pref);
        $this->assertStringContainsString('poisson', (string) ($pref->tastes['aversions'] ?? ''));
    }

    public function test_openai_quota_falls_back_to_local_for_free(): void
    {
        config([
            'synoria.companion.provider' => 'openai',
            'synoria.companion.api_key' => 'sk-proj-test-key-valid-looking',
            'synoria.companion.base_url' => 'https://api.openai.com/v1',
            'synoria.companion.model' => 'gpt-4o-mini',
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'quota']], 429),
        ]);

        $restaurant = Restaurant::factory()->create([
            'name' => 'Chez Budget',
            'is_validated' => true,
            'is_open' => true,
        ]);
        MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Riz sauce',
            'category' => MenuCategory::Plats->value,
            'price' => 2500,
            'is_available' => true,
        ]);

        $reply = $this->postJson(route('companion.message'), [
            'message' => 'J’ai 4000 FCFA',
            'restaurant_id' => $restaurant->id,
        ])
            ->assertOk()
            ->assertJsonPath('mode', 'local_fallback')
            ->json('reply');

        $this->assertStringContainsString('Riz sauce', $reply);
        $this->assertStringNotContainsString('quota', mb_strtolower($reply));
    }

    public function test_agent_explains_active_order_wait(): void
    {
        $customer = User::factory()->create();
        $restaurant = Restaurant::factory()->create([
            'prep_time_min' => 15,
            'prep_time_max' => 25,
            'is_validated' => true,
        ]);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => OrderStatus::Preparing,
            'number' => 'SE-WAIT-001',
        ]);

        $reply = $this->actingAs($customer)
            ->postJson(route('companion.message'), [
                'message' => 'Où en est ma commande ?',
            ])
            ->assertOk()
            ->json('reply');

        $this->assertStringContainsString($order->number, $reply);
        $this->assertStringContainsString('En préparation', $reply);
    }

    public function test_reset_clears_persisted_conversation(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->postJson(route('companion.message'), ['message' => 'Salut'])
            ->assertOk();

        $this->assertDatabaseCount('companion_messages', 2);

        $this->actingAs($customer)
            ->postJson(route('companion.reset'))
            ->assertOk();

        $this->assertDatabaseCount('companion_messages', 0);
        $this->assertSame(0, CompanionMessage::query()->count());
    }
}
