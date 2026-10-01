<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotchPayCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synoria.payments.sandbox' => false,
            'services.notchpay.public_key' => 'pk_test_feature',
            'services.notchpay.hash_key' => 'hash_test_secret',
            'services.notchpay.base_url' => 'https://api.notchpay.co',
        ]);
    }

    public function test_checkout_redirects_to_notchpay_and_webhook_marks_paid(): void
    {
        Http::fake([
            'https://api.notchpay.co/payments' => Http::response([
                'code' => 201,
                'transaction' => [
                    'reference' => 'trx.test123',
                    'status' => 'pending',
                ],
                'authorization_url' => 'https://pay.notchpay.co/trx.test123',
            ], 201),
        ]);

        [$customer] = $this->customerWithCart();

        $response = $this->actingAs($customer)->post(route('checkout.store'), [
            'delivery_address' => 'Rue 1, Yaoundé',
            'delivery_phone' => '+237655000000',
            'payment_method' => 'orange_money',
            'payment_phone' => '655000000',
        ]);

        $response->assertRedirect('https://pay.notchpay.co/trx.test123');

        $order = $customer->orders()->first();
        $this->assertNotNull($order);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);
        $this->assertSame('trx.test123', $order->payment_reference);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.notchpay.co/payments'
                && $request['locked_channel'] === 'cm.orange'
                && $request['currency'] === 'XAF'
                && $request['phone'] === '237655000000'
                && $request->hasHeader('Authorization', 'pk_test_feature');
        });

        $payload = json_encode([
            'event' => 'payment.complete',
            'data' => [
                'reference' => $order->number,
                'status' => 'complete',
            ],
        ], JSON_THROW_ON_ERROR);

        $this->call(
            'POST',
            '/api/payments/notchpay/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_NOTCH_SIGNATURE' => hash_hmac('sha256', $payload, 'hash_test_secret'),
            ],
            $payload,
        )->assertOk();

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        $this->call(
            'POST',
            '/api/payments/notchpay/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_NOTCH_SIGNATURE' => 'not-the-signature',
            ],
            '{"event":"payment.complete"}',
        )->assertForbidden();
    }

    /**
     * @return array{0: User}
     */
    private function customerWithCart(): array
    {
        $owner = User::factory()->restaurantOwner()->create();
        $customer = User::factory()->create(['role' => UserRole::Customer]);
        $restaurant = Restaurant::factory()->create([
            'owner_id' => $owner->id,
            'is_open' => true,
            'delivery_fee' => 500,
        ]);
        $item = MenuItem::factory()->create([
            'restaurant_id' => $restaurant->id,
            'price' => 2500,
            'is_available' => true,
        ]);

        $this->actingAs($customer)
            ->post(route('cart.store'), ['menu_item_id' => $item->id, 'quantity' => 2])
            ->assertRedirect();

        return [$customer];
    }
}
