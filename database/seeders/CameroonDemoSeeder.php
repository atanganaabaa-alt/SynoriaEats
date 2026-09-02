<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\MenuCategory;
use App\Enums\UserRole;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Restos camerounais de démo avec plats typiques et photos locales.
 *
 * php artisan db:seed --class=CameroonDemoSeeder
 */
class CameroonDemoSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'owner.demo@synoriaeats.test'],
            [
                'name' => 'Mama Ngono',
                'phone' => '695000100',
                'role' => UserRole::RestaurantOwner,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'approval_status' => ApprovalStatus::Approved,
                'approved_at' => now(),
                'is_active' => true,
            ]
        );

        $restaurants = [
            [
                'name' => 'Chez Maman Ngono',
                'slug' => 'chez-maman-ngono',
                'address' => 'Rue 1.543, Bastos, Yaoundé',
                'description' => 'Cuisine familiale camerounaise : ndolé, eru, koki et sauce arachide comme à la maison.',
                'category' => 'Africain',
                'cover_url' => 'images/food/ndole.jpg',
                'logo_url' => 'images/food/soup.jpg',
                'latitude' => 3.8880,
                'longitude' => 11.5080,
                'rating' => 4.8,
                'delivery_fee' => 500,
                'items' => [
                    ['Ndolé crevettes', 'Feuilles de ndolé, crevettes, arachides et viande', 4500, 'images/food/ndole.jpg', MenuCategory::Plats],
                    ['Eru & water fufu', 'Légumes eru, peau de boeuf et fufu', 4000, 'images/food/soup.jpg', MenuCategory::Plats],
                    ['Koki haricot', 'Gâteau de haricots au feuille de plantain', 2500, 'images/food/stew.jpg', MenuCategory::Plats],
                    ['Jus de gingembre', 'Frais, maison', 1000, 'images/food/salad.jpg', MenuCategory::Boissons],
                ],
            ],
            [
                'name' => 'Grillades du Quartier',
                'slug' => 'grillades-du-quartier',
                'address' => 'Avenue Ahmadou Ahidjo, Mvog-Ada, Yaoundé',
                'description' => 'Poisson braisé, soya, poulet DG et plantains frits : le vrai goût du coin.',
                'category' => 'Grillades',
                'cover_url' => 'images/food/fish.jpg',
                'logo_url' => 'images/food/grilled.jpg',
                'latitude' => 3.8605,
                'longitude' => 11.5210,
                'rating' => 4.6,
                'delivery_fee' => 750,
                'items' => [
                    ['Poisson braisé', 'Capitaine grillé, sauce piment, plantains', 5500, 'images/food/fish.jpg', MenuCategory::Plats],
                    ['Poulet DG', 'Poulet sauté, plantains, légumes', 5000, 'images/food/chicken.jpg', MenuCategory::Plats],
                    ['Soya brochettes', 'Brochettes de boeuf marinées', 2000, 'images/food/grilled.jpg', MenuCategory::Plats],
                    ['Plantains frits', 'Portion généreuse', 1500, 'images/food/plantain.jpg', MenuCategory::Accompagnements],
                    ['Beaufort / Dschang', 'Bière locale (33 cl)', 1200, 'images/food/burger.jpg', MenuCategory::Boissons],
                ],
            ],
            [
                'name' => 'Saveurs de Douala',
                'slug' => 'saveurs-de-douala',
                'address' => 'Carrefour Nsimeyong, Yaoundé',
                'description' => 'Mbongo tchobi, kondré, riz sauce et mets du Littoral à Yaoundé.',
                'category' => 'Africain',
                'cover_url' => 'images/food/rice.jpg',
                'logo_url' => 'images/food/plantain.jpg',
                'latitude' => 3.8375,
                'longitude' => 11.4855,
                'rating' => 4.7,
                'delivery_fee' => 600,
                'items' => [
                    ['Mbongo tchobi', 'Poisson en sauce noire épicée', 4800, 'images/food/stew2.jpg', MenuCategory::Plats],
                    ['Kondré banane', 'Banane plantain et viande de chèvre', 4200, 'images/food/plantain.jpg', MenuCategory::Plats],
                    ['Riz sauce arachide', 'Riz blanc, sauce arachide, poulet', 3500, 'images/food/rice.jpg', MenuCategory::Plats],
                    ['Bissap', 'Jus d’hibiscus maison', 800, 'images/food/salad.jpg', MenuCategory::Boissons],
                    ['Beignets haricot', 'Accra croustillants', 1000, 'images/food/burger.jpg', MenuCategory::Desserts],
                ],
            ],
            [
                'name' => 'Le Bistrot Camerounais',
                'slug' => 'le-bistrot-camerounais',
                'address' => 'Boulevard du 20 Mai, Centre-ville, Yaoundé',
                'description' => 'Assiettes complètes, grillades et plats du jour camerounais, service rapide.',
                'category' => 'Africain',
                'cover_url' => 'images/food/hero.jpg',
                'logo_url' => 'images/food/chicken.jpg',
                'latitude' => 3.8660,
                'longitude' => 11.5165,
                'rating' => 4.5,
                'delivery_fee' => 500,
                'items' => [
                    ['Assiette complète', 'Riz, sauce, viande et légumes', 4000, 'images/food/hero.jpg', MenuCategory::Plats],
                    ['Poulet braisé', 'Demi-poulet, sauce maison', 4500, 'images/food/chicken.jpg', MenuCategory::Plats],
                    ['Salade fraîcheur', 'Légumes croquants, vinaigrette', 2500, 'images/food/stew2.jpg', MenuCategory::Plats],
                    ['Jus d’ananas', 'Pressé du jour', 1000, 'images/food/salad.jpg', MenuCategory::Boissons],
                ],
            ],
        ];

        foreach ($restaurants as $data) {
            $items = $data['items'];
            unset($data['items']);

            $restaurant = Restaurant::query()->updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'owner_id' => $owner->id,
                    'opening_hours' => '10:00-22:00',
                    'review_count' => random_int(24, 180),
                    'prep_time_min' => 25,
                    'prep_time_max' => 45,
                    'is_open' => true,
                    'is_validated' => true,
                    'status' => ApprovalStatus::Approved,
                    'reviewed_at' => now(),
                ])
            );

            foreach ($items as [$name, $description, $price, $photo, $category]) {
                MenuItem::query()->updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name' => $name,
                    ],
                    [
                        'description' => $description,
                        'price' => $price,
                        'photo_url' => $photo,
                        'category' => $category->value,
                        'is_available' => true,
                    ]
                );
            }

            // Lien accompagnement plantains → plats grillés si present
            $plantains = MenuItem::query()
                ->where('restaurant_id', $restaurant->id)
                ->where('name', 'Plantains frits')
                ->first();

            if ($plantains) {
                $dishes = MenuItem::query()
                    ->where('restaurant_id', $restaurant->id)
                    ->where('category', MenuCategory::Plats->value)
                    ->whereIn('name', ['Poisson braisé', 'Poulet DG', 'Soya brochettes'])
                    ->get();

                foreach ($dishes as $dish) {
                    $dish->accompanimentOptions()->syncWithoutDetaching([
                        $plantains->id => ['extra_price' => 0, 'is_available' => true],
                    ]);
                }
            }
        }

        $this->command?->info('4 restaurants camerounais prêts (owner.demo@synoriaeats.test / password).');
    }
}
