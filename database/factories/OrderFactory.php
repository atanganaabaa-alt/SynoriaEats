<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(3000, 20000);
        $deliveryFee = fake()->randomElement([0, 500, 1000]);
        $commission = (int) round($subtotal * 0.10);

        return [
            'number' => 'SE-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'customer_id' => User::factory(),
            'restaurant_id' => Restaurant::factory(),
            'courier_id' => null,
            'delivery_address' => fake()->streetAddress().', '.fake()->city(),
            'delivery_phone' => fake()->numerify('+237 6## ## ## ##'),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'commission' => $commission,
            'total' => $subtotal + $deliveryFee,
            'payment_method' => fake()->randomElement(PaymentMethod::cases()),
            'payment_status' => PaymentStatus::Pending,
            'payment_reference' => null,
            'status' => OrderStatus::Pending,
            'courier_lat' => null,
            'courier_lng' => null,
            'notes' => null,
            'delivered_at' => null,
        ];
    }
}
