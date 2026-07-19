<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::factory()->create();

        return [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'restaurant_id' => $order->restaurant_id,
            'courier_id' => $order->courier_id,
            'restaurant_rating' => fake()->numberBetween(3, 5),
            'courier_rating' => fake()->optional()->numberBetween(3, 5),
            'comment' => fake()->optional()->sentence(12),
        ];
    }
}
