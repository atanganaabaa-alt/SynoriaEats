<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\MenuCategory;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Collection;

class RestaurantMatcher
{
    public function __construct(
        private DeliveryFeeCalculator $fees,
        private CameroonPlaceGeocoder $geocoder,
    ) {}

    public const PRESETS = [
        'balanced' => [
            'label' => 'Équilibré',
            'description' => 'Bon mix distance, prix et qualité.',
            'weights' => ['distance' => 4, 'price' => 2, 'fee' => 2, 'courier' => 1, 'rating' => 1],
        ],
        'nearby' => [
            'label' => 'Le plus proche',
            'description' => 'Priorité aux restaurants autour de toi.',
            'weights' => ['distance' => 4, 'price' => 1, 'fee' => 1, 'courier' => 2, 'rating' => 1],
        ],
        'budget' => [
            'label' => 'Petit budget',
            'description' => 'Plats abordables et frais de livraison bas.',
            'weights' => ['distance' => 1, 'price' => 4, 'fee' => 3, 'courier' => 1, 'rating' => 1],
        ],
        'rated' => [
            'label' => 'Mieux notés',
            'description' => 'Les favoris des autres clients.',
            'weights' => ['distance' => 1, 'price' => 1, 'fee' => 1, 'courier' => 1, 'rating' => 4],
        ],
        'fast' => [
            'label' => 'Livraison rapide',
            'description' => 'Livreurs disponibles et restaurant proche.',
            'weights' => ['distance' => 3, 'price' => 1, 'fee' => 1, 'courier' => 4, 'rating' => 1],
        ],
    ];

    private const DEFAULT_WEIGHTS = [
        'distance' => 4,
        'price' => 2,
        'fee' => 2,
        'courier' => 1,
        'rating' => 1,
    ];

    /**
     * @param  Collection<int, Restaurant>  $restaurants
     * @return Collection<int, Restaurant>
     */
    public function rank(Collection $restaurants, float $lat, float $lng, array $weights = [], bool $strictDistance = true): Collection
    {
        if ($restaurants->isEmpty()) {
            return $restaurants;
        }

        $couriers = $this->availableCouriers();
        $weights = $this->normalizeWeights($weights);
        $maxDistanceKm = (float) config('synoria.matching.max_distance_km', 12);

        $metrics = $restaurants->map(function (Restaurant $restaurant) use ($lat, $lng, $couriers) {
            $coords = $this->restaurantCoordinates($restaurant);
            $distanceKm = null;
            if ($coords !== null) {
                $distanceKm = $this->fees->distanceKm(
                    $lat,
                    $lng,
                    $coords['lat'],
                    $coords['lng']
                );
            }

            $avgPrice = $this->averageDishPrice($restaurant);
            $estimatedFee = $coords !== null
                ? $this->fees->forRestaurant($restaurant, $lat, $lng, (int) $avgPrice)
                : (int) ($restaurant->delivery_fee ?? 0) + 1500;
            $nearbyCouriers = $this->nearbyCourierCount($restaurant, $couriers);

            return [
                'restaurant' => $restaurant,
                'distance_km' => $distanceKm,
                'avg_dish_price' => (int) round($avgPrice),
                'estimated_fee' => $estimatedFee,
                'nearby_couriers' => $nearbyCouriers,
                'rating' => (float) $restaurant->rating,
                'place_label' => $coords['label'] ?? null,
            ];
        });

        $distances = $metrics->pluck('distance_km')->filter();
        $prices = $metrics->pluck('avg_dish_price')->filter(fn ($v) => $v > 0);
        $fees = $metrics->pluck('estimated_fee');
        $ratings = $metrics->pluck('rating');

        $minDistance = $distances->min() ?? 0;
        $maxDistance = max($minDistance + 0.1, $distances->max() ?? 15);
        $minPrice = $prices->min() ?? 0;
        $maxPrice = max($minPrice + 1, $prices->max() ?? 1);
        $minFee = $fees->min() ?? 0;
        $maxFee = max($minFee + 1, $fees->max() ?? 1);
        $minRating = $ratings->min() ?? 0;
        $maxRating = max($minRating + 0.1, $ratings->max() ?? 5);
        $maxCouriers = max(1, (int) $metrics->max('nearby_couriers'));

        $ranked = $metrics->map(function (array $row) use (
            $weights,
            $minDistance,
            $maxDistance,
            $minPrice,
            $maxPrice,
            $minFee,
            $maxFee,
            $minRating,
            $maxRating,
            $maxCouriers,
            $maxDistanceKm,
            $strictDistance,
        ) {
            /** @var Restaurant $restaurant */
            $restaurant = $row['restaurant'];
            $distanceKm = $row['distance_km'];

            if ($strictDistance) {
                // Sans position exploitable, on ne peut pas livrer « proche » : on exclut.
                if ($distanceKm === null) {
                    return null;
                }
                if ($distanceKm > $maxDistanceKm) {
                    return null;
                }
            }

            $dimensionScores = [
                'distance' => $this->normalizeLowerIsBetter($distanceKm, $minDistance, $maxDistance, 20),
                'price' => $this->normalizeLowerIsBetter(
                    $row['avg_dish_price'] > 0 ? $row['avg_dish_price'] : null,
                    $minPrice,
                    $maxPrice,
                    40
                ),
                'fee' => $this->normalizeLowerIsBetter($row['estimated_fee'], $minFee, $maxFee, 40),
                'courier' => $this->normalizeHigherIsBetter($row['nearby_couriers'], 0, $maxCouriers, 35),
                'rating' => $this->normalizeHigherIsBetter($row['rating'], $minRating, $maxRating, 30),
            ];

            $weightSum = max(1, array_sum($weights));
            $matchScore = (int) round(collect($dimensionScores)->sum(
                fn ($score, $key) => $score * $weights[$key]
            ) / $weightSum);

            $highlights = $this->buildHighlights($row, $dimensionScores);

            $restaurant->setAttribute('match_score', $matchScore);
            $restaurant->setAttribute('distance_km', $distanceKm);
            $restaurant->setAttribute('estimated_fee', $row['estimated_fee']);
            $restaurant->setAttribute('avg_dish_price', $row['avg_dish_price']);
            $restaurant->setAttribute('nearby_couriers', $row['nearby_couriers']);
            $restaurant->setAttribute('match_highlights', $highlights);
            $restaurant->setAttribute('match_dimension_scores', $dimensionScores);
            $restaurant->setAttribute('match_weights', $weights);
            $restaurant->setAttribute('place_label', $row['place_label'] ?? null);

            return $restaurant;
        })->filter()->values();

        $this->assignBadges($ranked);

        return $ranked
            ->sortBy([
                fn (Restaurant $r) => -1 * (int) $r->match_score,
                fn (Restaurant $r) => $r->distance_km ?? 9999,
            ])
            ->values();
    }

    public function defaultWeights(): array
    {
        return self::DEFAULT_WEIGHTS;
    }

    public function weightsForPreset(?string $preset): array
    {
        if ($preset && isset(self::PRESETS[$preset])) {
            return self::PRESETS[$preset]['weights'];
        }

        return self::DEFAULT_WEIGHTS;
    }

    public function normalizeWeights(array $weights): array
    {
        $normalized = [];

        foreach (self::DEFAULT_WEIGHTS as $key => $default) {
            $value = $weights[$key] ?? $default;
            $normalized[$key] = max(0, min(4, (int) $value));
        }

        if (array_sum($normalized) === 0) {
            return self::DEFAULT_WEIGHTS;
        }

        return $normalized;
    }

    private function normalizeLowerIsBetter(?float $value, float $min, float $max, int $nullScore): int
    {
        if ($value === null) {
            return $nullScore;
        }

        if ($max <= $min) {
            return 100;
        }

        return (int) round(100 * (1 - (($value - $min) / ($max - $min))));
    }

    private function normalizeHigherIsBetter(float $value, float $min, float $max, int $floor): int
    {
        if ($max <= $min) {
            return 100;
        }

        return max($floor, (int) round(100 * (($value - $min) / ($max - $min))));
    }

    /**
     * @param  array<string, int>  $dimensionScores
     * @return array<int, string>
     */
    private function buildHighlights(array $row, array $dimensionScores): array
    {
        $highlights = [];

        if ($row['distance_km'] !== null) {
            $highlights[] = 'À '.number_format($row['distance_km'], 1, ',', ' ').' km';
        }

        $highlights[] = 'Livraison ~'.number_format($row['estimated_fee'], 0, ',', ' ').' FCFA';

        if ($row['avg_dish_price'] > 0) {
            $highlights[] = 'Plats ~'.number_format($row['avg_dish_price'], 0, ',', ' ').' FCFA';
        }

        if ($row['nearby_couriers'] > 0) {
            $highlights[] = $row['nearby_couriers'].' livreur'.($row['nearby_couriers'] > 1 ? 's' : '').' proche'.($row['nearby_couriers'] > 1 ? 's' : '');
        }

        arsort($dimensionScores);
        $topKey = array_key_first($dimensionScores);

        $strengthLabels = [
            'distance' => 'Très proche de toi',
            'price' => 'Prix attractifs',
            'fee' => 'Frais de livraison bas',
            'courier' => 'Bonne dispo livreurs',
            'rating' => 'Très bien noté',
        ];

        if ($topKey && ($dimensionScores[$topKey] ?? 0) >= 75 && isset($strengthLabels[$topKey])) {
            array_unshift($highlights, $strengthLabels[$topKey]);
        }

        return array_values(array_unique($highlights));
    }

    /**
     * @param  Collection<int, Restaurant>  $restaurants
     */
    private function assignBadges(Collection $restaurants): void
    {
        if ($restaurants->isEmpty()) {
            return;
        }

        $bestMatch = $restaurants->sortByDesc('match_score')->first();
        $closest = $restaurants
            ->filter(fn (Restaurant $r) => $r->distance_km !== null)
            ->sortBy('distance_km')
            ->first();
        $cheapestFee = $restaurants->sortBy('estimated_fee')->first();
        $bestRated = $restaurants->sortByDesc('rating')->first();

        $restaurants->each(function (Restaurant $restaurant) use ($bestMatch, $closest, $cheapestFee, $bestRated) {
            $badges = [];

            if ($bestMatch && $restaurant->is($bestMatch)) {
                $badges[] = 'Meilleure suggestion';
            }
            if ($closest && $restaurant->is($closest)) {
                $badges[] = 'Le plus proche';
            }
            if ($cheapestFee && $restaurant->is($cheapestFee)) {
                $badges[] = 'Frais les plus bas';
            }
            if ($bestRated && $restaurant->is($bestRated) && (float) $restaurant->rating >= 4) {
                $badges[] = 'Top note';
            }

            $restaurant->setAttribute('match_badges', array_values(array_unique($badges)));
        });
    }

    private function averageDishPrice(Restaurant $restaurant): float
    {
        $items = $restaurant->relationLoaded('menuItems')
            ? $restaurant->menuItems
            : $restaurant->menuItems()->where('is_available', true)->get();

        $plats = $items
            ->where('is_available', true)
            ->filter(fn ($item) => $item->category === MenuCategory::Plats->value);

        if ($plats->isEmpty()) {
            $plats = $items->where('is_available', true);
        }

        return (float) ($plats->avg('price') ?: 0);
    }

    /**
     * @return Collection<int, User>
     */
    private function availableCouriers(): Collection
    {
        $busyIds = \App\Models\Order::query()
            ->where('status', OrderStatus::OutForDelivery)
            ->whereNotNull('courier_id')
            ->pluck('courier_id');

        return User::query()
            ->where('role', UserRole::Courier)
            ->where('is_active', true)
            ->where('approval_status', ApprovalStatus::Approved)
            ->whereNotIn('id', $busyIds)
            ->get();
    }

    /**
     * @param  Collection<int, User>  $couriers
     */
    private function nearbyCourierCount(Restaurant $restaurant, Collection $couriers): int
    {
        $coords = $this->restaurantCoordinates($restaurant);
        if ($coords === null) {
            return 0;
        }

        $radiusKm = (float) config('synoria.matching.courier_radius_km', 8);
        $freshMinutes = (int) config('synoria.matching.courier_fresh_minutes', 45);

        return $couriers
            ->filter(function (User $courier) use ($coords, $radiusKm, $freshMinutes) {
                if ($courier->last_lat === null || $courier->last_lng === null) {
                    return false;
                }

                if ($courier->last_seen_at && $courier->last_seen_at->lt(now()->subMinutes($freshMinutes))) {
                    return false;
                }

                $km = $this->fees->distanceKm(
                    $coords['lat'],
                    $coords['lng'],
                    (float) $courier->last_lat,
                    (float) $courier->last_lng
                );

                return $km <= $radiusKm;
            })
            ->count();
    }

    /**
     * @return array{lat: float, lng: float, label: ?string}|null
     */
    public function restaurantCoordinates(Restaurant $restaurant): ?array
    {
        if ($restaurant->latitude !== null && $restaurant->longitude !== null) {
            return [
                'lat' => (float) $restaurant->latitude,
                'lng' => (float) $restaurant->longitude,
                'label' => null,
            ];
        }

        $resolved = $this->geocoder->resolve(
            $restaurant->address,
            $restaurant->city ?? null,
            $restaurant->neighborhood ?? null,
        );

        if ($resolved === null) {
            return null;
        }

        return [
            'lat' => $resolved['lat'],
            'lng' => $resolved['lng'],
            'label' => $resolved['label'],
        ];
    }
}
