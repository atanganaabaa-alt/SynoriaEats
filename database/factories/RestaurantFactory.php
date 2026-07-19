<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Kitchen';

        return [
            'owner_id' => User::factory()->restaurantOwner(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'description' => fake()->sentence(12),
            'logo_url' => null,
            'cover_url' => null,
            'category' => fake()->randomElement(['Africain', 'Fast-food', 'Pizza', 'Sushi', 'Healthy', 'Desserts']),
            'opening_hours' => '09:00-22:00',
            'rating' => fake()->randomFloat(1, 3.5, 5.0),
            'review_count' => fake()->numberBetween(0, 250),
            'prep_time_min' => 20,
            'prep_time_max' => 45,
            'delivery_fee' => fake()->randomElement([0, 500, 750, 1000]),
            'latitude' => fake()->latitude(3.8, 4.1),
            'longitude' => fake()->longitude(11.4, 11.6),
            'is_open' => true,
        ];
    }
}
