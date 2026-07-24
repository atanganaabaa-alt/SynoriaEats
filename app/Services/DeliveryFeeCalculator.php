<?php

namespace App\Services;

use App\Models\Restaurant;
use Carbon\CarbonInterface;

class DeliveryFeeCalculator
{
    /**
     * Haversine distance in kilometers.
     */
    public function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    /**
     * @return array{
     *     fee: int,
     *     distance_km: float|null,
     *     zone: string|null,
     *     breakdown: array<string, int>
     * }
     */
    public function quote(
        Restaurant $restaurant,
        ?float $deliveryLat,
        ?float $deliveryLng,
        int $subtotal = 0,
        ?CarbonInterface $at = null,
    ): array {
        $at ??= now();
        $breakdown = [];
        $base = (int) ($restaurant->delivery_fee ?? 0);
        $breakdown['base'] = $base;

        $distanceKm = null;
        $zone = null;

        if ($deliveryLat !== null && $deliveryLng !== null
            && $restaurant->latitude !== null && $restaurant->longitude !== null) {
            $distanceKm = $this->distanceKm(
                (float) $restaurant->latitude,
                (float) $restaurant->longitude,
                $deliveryLat,
                $deliveryLng
            );

            $breakdown['distance'] = $this->distanceComponent($distanceKm);
            $zone = $this->resolveZone($deliveryLat, $deliveryLng);
            if ($zone !== null) {
                $zoneSurcharge = (int) config("synoria.delivery.zones.{$zone}.surcharge", 0);
                if ($zoneSurcharge > 0) {
                    $breakdown['zone_'.$zone] = $zoneSurcharge;
                }
            }
        }

        $hour = (int) $at->format('G');
        $nightStart = (int) config('synoria.delivery.night_start', 22);
        $nightEnd = (int) config('synoria.delivery.night_end', 6);
        $isNight = $nightStart > $nightEnd
            ? ($hour >= $nightStart || $hour < $nightEnd)
            : ($hour >= $nightStart && $hour < $nightEnd);

        if ($isNight) {
            $breakdown['night'] = (int) config('synoria.delivery.night_surcharge', 300);
        }

        $peakWindows = config('synoria.delivery.peak_hours', [[12, 14], [19, 21]]);
        foreach ($peakWindows as $window) {
            [$start, $end] = $window;
            if ($hour >= (int) $start && $hour < (int) $end) {
                $breakdown['peak'] = (int) config('synoria.delivery.peak_surcharge', 200);
                break;
            }
        }

        $basketThreshold = (int) config('synoria.delivery.small_order_threshold', 3000);
        if ($subtotal > 0 && $subtotal < $basketThreshold) {
            $breakdown['small_order'] = (int) config('synoria.delivery.small_order_surcharge', 250);
        }

        $largeThreshold = (int) config('synoria.delivery.large_order_threshold', 25000);
        if ($subtotal >= $largeThreshold) {
            $breakdown['large_order'] = (int) config('synoria.delivery.large_order_surcharge', 500);
        }

        $fee = array_sum($breakdown);
        $minFee = (int) config('synoria.delivery.min_fee', 0);
        $maxFee = (int) config('synoria.delivery.max_fee', 5000);

        if ($fee < $minFee) {
            $breakdown['min_adjust'] = $minFee - $fee;
            $fee = $minFee;
        }

        if ($fee > $maxFee) {
            $breakdown['cap'] = -($fee - $maxFee);
            $fee = $maxFee;
        }

        return [
            'fee' => $fee,
            'distance_km' => $distanceKm,
            'zone' => $zone,
            'breakdown' => $breakdown,
        ];
    }

    public function forRestaurant(
        Restaurant $restaurant,
        ?float $deliveryLat,
        ?float $deliveryLng,
        int $subtotal = 0,
        ?CarbonInterface $at = null,
    ): int {
        return $this->quote($restaurant, $deliveryLat, $deliveryLng, $subtotal, $at)['fee'];
    }

    private function distanceComponent(float $km): int
    {
        $tiers = config('synoria.delivery.distance_tiers', [
            ['max_km' => 2, 'fee' => 300],
            ['max_km' => 5, 'fee' => 600],
            ['max_km' => 10, 'fee' => 1000],
            ['max_km' => 20, 'fee' => 1800],
        ]);

        foreach ($tiers as $tier) {
            if ($km <= (float) $tier['max_km']) {
                return (int) $tier['fee'];
            }
        }

        $extraKm = max(0, $km - 20);
        $perKm = (int) config('synoria.delivery.fee_per_km', 200);

        return 1800 + (int) round($extraKm * $perKm);
    }

    private function resolveZone(float $lat, float $lng): ?string
    {
        $zones = config('synoria.delivery.zones', []);

        foreach ($zones as $name => $zone) {
            $centerLat = (float) ($zone['lat'] ?? 0);
            $centerLng = (float) ($zone['lng'] ?? 0);
            $radius = (float) ($zone['radius_km'] ?? 0);

            if ($radius <= 0) {
                continue;
            }

            if ($this->distanceKm($lat, $lng, $centerLat, $centerLng) <= $radius) {
                return (string) $name;
            }
        }

        return null;
    }
}
