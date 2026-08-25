<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Géocode une adresse resto → lat/lng.
 * 1) Nominatim (OpenStreetMap) pour une position carte réelle
 * 2) Fallback lieux Cameroun connus (Bastos, Melen, Odza, Ambam…)
 */
class RestaurantGeocoder
{
    public function __construct(private CameroonPlaceGeocoder $localPlaces) {}

    /**
     * @return array{lat: float, lng: float, label: string, source: string}|null
     */
    public function geocode(?string $address, ?string $city = null, ?string $neighborhood = null): ?array
    {
        $query = trim(implode(', ', array_filter([$address, $city, $neighborhood, 'Cameroun'])));

        if ($query === '' || $query === 'Cameroun') {
            return null;
        }

        $remote = $this->nominatim($query);
        if ($remote !== null) {
            return $remote;
        }

        $local = $this->localPlaces->resolve($address, $city, $neighborhood);
        if ($local === null) {
            return null;
        }

        return [
            'lat' => $local['lat'],
            'lng' => $local['lng'],
            'label' => $local['label'],
            'source' => 'local',
        ];
    }

    /**
     * Applique le géocodage sur un tableau de données restaurant (create/update).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyToRestaurantData(array $data, bool $force = false): array
    {
        $hasCoords = filled($data['latitude'] ?? null) && filled($data['longitude'] ?? null);

        if ($hasCoords && ! $force) {
            return $data;
        }

        $place = $this->geocode(
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['neighborhood'] ?? null,
        );

        if ($place === null) {
            return $data;
        }

        $data['latitude'] = $place['lat'];
        $data['longitude'] = $place['lng'];

        return $data;
    }

    /**
     * @return array{lat: float, lng: float, label: string, source: string}|null
     */
    private function nominatim(string $query): ?array
    {
        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => 'SynoriaEats/1.0 (restaurant-geocoder; contact@synoria.local)',
                    'Accept-Language' => 'fr',
                ])
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                    'countrycodes' => 'cm',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $row = $response->json('0');
            if (! is_array($row) || ! isset($row['lat'], $row['lon'])) {
                // Retry without country filter if Cameroon-only returned nothing
                $response = Http::timeout(8)
                    ->withHeaders([
                        'User-Agent' => 'SynoriaEats/1.0 (restaurant-geocoder; contact@synoria.local)',
                        'Accept-Language' => 'fr',
                    ])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $query,
                        'format' => 'json',
                        'limit' => 1,
                    ]);

                $row = $response->successful() ? $response->json('0') : null;
            }

            if (! is_array($row) || ! isset($row['lat'], $row['lon'])) {
                return null;
            }

            $label = (string) ($row['display_name'] ?? $query);

            return [
                'lat' => round((float) $row['lat'], 7),
                'lng' => round((float) $row['lon'], 7),
                'label' => Str::limit($label, 160),
                'source' => 'nominatim',
            ];
        } catch (\Throwable $e) {
            Log::warning('Nominatim geocode failed', ['error' => $e->getMessage(), 'query' => $query]);

            return null;
        }
    }
}
