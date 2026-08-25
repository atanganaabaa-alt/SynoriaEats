<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Services\RestaurantGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    public function __invoke(Request $request, RestaurantGeocoder $geocoder): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'min:3', 'max:255'],
        ]);

        $place = $geocoder->geocode($validated['address']);

        if ($place === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Adresse introuvable sur la carte. Précise le quartier (ex. Bastos Yaoundé).',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'lat' => $place['lat'],
            'lng' => $place['lng'],
            'label' => $place['label'],
            'source' => $place['source'],
        ]);
    }
}
