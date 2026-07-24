<?php

namespace App\Services;

use App\Models\Restaurant;

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

    public function forRestaurant(Restaurant $restaurant, ?float $deliveryLat, ?float $deliveryLng): int
    {
        $base = (int) ($restaurant->delivery_fee ?? 0);
        $perKm = (int) config('synoria.delivery.fee_per_km', 200);
        $minFee = (int) config('synoria.delivery.min_fee', 0);

        if ($deliveryLat === null || $deliveryLng === null) {
            return max($base, $minFee);
        }

        if ($restaurant->latitude === null || $restaurant->longitude === null) {
            return max($base, $minFee);
        }

        $km = $this->distanceKm(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            $deliveryLat,
            $deliveryLng
        );

        $distanceFee = (int) round($km * $perKm);

        return max($base + $distanceFee, $minFee);
    }
}
