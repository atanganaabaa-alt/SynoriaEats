<?php

namespace App\Services;

/**
 * Résout une position approximative à partir d’une adresse texte (Cameroun / Yaoundé).
 * Utile tant que les restos n’ont pas encore de GPS saisi à la création.
 */
class CameroonPlaceGeocoder
{
    /**
     * Points de référence (lat, lng).
     *
     * @var array<string, array{0: float, 1: float, 2: string}>
     */
    private const PLACES = [
        'odza' => [3.8515, 11.5770, 'Odza'],
        'bastos' => [3.8920, 11.5140, 'Bastos'],
        'melen' => [3.8660, 11.4920, 'Melen'],
        'nkolbisson' => [3.8700, 11.4700, 'Nkolbisson'],
        'mokolo' => [3.8800, 11.5000, 'Mokolo'],
        'essos' => [3.8750, 11.5400, 'Essos'],
        'ngousso' => [3.9000, 11.5400, 'Ngousso'],
        'emana' => [3.9200, 11.5200, 'Emana'],
        'centre' => [3.8667, 11.5167, 'Centre-ville Yaoundé'],
        'yaounde' => [3.8667, 11.5167, 'Yaoundé'],
        'yaoundé' => [3.8667, 11.5167, 'Yaoundé'],
        'douala' => [4.0511, 9.7679, 'Douala'],
        'akwa' => [4.0500, 9.7000, 'Akwa'],
        'ambam' => [2.3833, 11.2833, 'Ambam'],
        'nkoumekeke' => [2.3900, 11.2900, 'Nkoumekeke'],
        'kribi' => [2.9373, 9.9070, 'Kribi'],
        'bafoussam' => [5.4778, 10.4176, 'Bafoussam'],
    ];

    /**
     * @return array{lat: float, lng: float, label: string}|null
     */
    public function resolve(?string $address, ?string $city = null, ?string $neighborhood = null): ?array
    {
        $haystack = mb_strtolower(trim(implode(' ', array_filter([
            $address,
            $city,
            $neighborhood,
        ]))), 'UTF-8');

        if ($haystack === '') {
            return null;
        }

        // Plus spécifique d’abord (Ambam avant Yaoundé, Bastos avant Yaoundé…)
        $ordered = [
            'nkoumekeke', 'ambam', 'kribi', 'bafoussam', 'akwa', 'douala',
            'bastos', 'melen', 'odza', 'nkolbisson', 'mokolo', 'essos', 'ngousso', 'emana',
            'centre', 'yaoundé', 'yaounde',
        ];

        foreach ($ordered as $key) {
            if (! isset(self::PLACES[$key])) {
                continue;
            }
            if (str_contains($haystack, $key)) {
                [$lat, $lng, $label] = self::PLACES[$key];

                return ['lat' => $lat, 'lng' => $lng, 'label' => $label];
            }
        }

        return null;
    }

    /**
     * @return array{lat: float, lng: float}
     */
    public function point(string $placeKey): array
    {
        $key = mb_strtolower($placeKey, 'UTF-8');
        if (! isset(self::PLACES[$key])) {
            throw new \InvalidArgumentException('Lieu inconnu: '.$placeKey);
        }

        return [
            'lat' => self::PLACES[$key][0],
            'lng' => self::PLACES[$key][1],
        ];
    }
}
