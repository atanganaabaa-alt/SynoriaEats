<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->randomElement([
                'Poulet braisé',
                'Ndolé',
                'Pizza Margherita',
                'Burger classic',
                'Attiéké poisson',
                'Bowl quinoa',
                'Sushi mix',
                'Tiramisu',
            ]),
            'description' => fake()->sentence(10),
            'price' => fake()->randomElement([1500, 2500, 3500, 4500, 6000, 8000]),
            'photo_url' => null,
            'category' => fake()->randomElement(['Plats', 'Entrées', 'Boissons', 'Desserts']),
            'is_available' => true,
        ];
    }
}
