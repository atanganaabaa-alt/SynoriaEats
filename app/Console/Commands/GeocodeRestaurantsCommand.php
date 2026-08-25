<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Services\RestaurantGeocoder;
use Illuminate\Console\Command;

class GeocodeRestaurantsCommand extends Command
{
    protected $signature = 'synoria:geocode-restaurants {--force : Recalcule même si lat/lng existent}';

    protected $description = 'Géocode les restaurants sans GPS à partir de leur adresse';

    public function handle(RestaurantGeocoder $geocoder): int
    {
        $force = (bool) $this->option('force');
        $query = Restaurant::query()->orderBy('id');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $count = 0;
        $skipped = 0;

        $query->each(function (Restaurant $restaurant) use ($geocoder, $force, &$count, &$skipped) {
            if (! $force && $restaurant->latitude !== null && $restaurant->longitude !== null) {
                $skipped++;

                return;
            }

            $place = $geocoder->geocode($restaurant->address);
            if ($place === null) {
                $this->warn("Skip #{$restaurant->id} {$restaurant->name} ({$restaurant->address})");
                $skipped++;

                return;
            }

            $restaurant->update([
                'latitude' => $place['lat'],
                'longitude' => $place['lng'],
            ]);
            $count++;
            $this->info("OK #{$restaurant->id} {$restaurant->name} → {$place['lat']},{$place['lng']} ({$place['source']})");
        });

        $this->line("Géocodés: {$count} · ignorés: {$skipped}");

        return self::SUCCESS;
    }
}
