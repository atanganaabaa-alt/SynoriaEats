<?php

return [

    'commission_rate' => (float) env('SYNORIA_COMMISSION_RATE', 0.10),

    'payments' => [
        'sandbox' => (bool) env('SYNORIA_PAYMENTS_SANDBOX', true),
    ],

    'notifications' => [
        'channels' => env('SYNORIA_NOTIFICATION_CHANNELS', 'log'),
    ],

    'delivery' => [
        'fee_per_km' => (int) env('SYNORIA_DELIVERY_FEE_PER_KM', 200),
        'min_fee' => (int) env('SYNORIA_DELIVERY_MIN_FEE', 500),
        'max_fee' => (int) env('SYNORIA_DELIVERY_MAX_FEE', 5000),
        'night_start' => (int) env('SYNORIA_DELIVERY_NIGHT_START', 22),
        'night_end' => (int) env('SYNORIA_DELIVERY_NIGHT_END', 6),
        'night_surcharge' => (int) env('SYNORIA_DELIVERY_NIGHT_SURCHARGE', 300),
        'peak_hours' => [[12, 14], [19, 21]],
        'peak_surcharge' => (int) env('SYNORIA_DELIVERY_PEAK_SURCHARGE', 200),
        'small_order_threshold' => (int) env('SYNORIA_DELIVERY_SMALL_ORDER', 3000),
        'small_order_surcharge' => (int) env('SYNORIA_DELIVERY_SMALL_SURCHARGE', 250),
        'large_order_threshold' => (int) env('SYNORIA_DELIVERY_LARGE_ORDER', 25000),
        'large_order_surcharge' => (int) env('SYNORIA_DELIVERY_LARGE_SURCHARGE', 500),
        'distance_tiers' => [
            ['max_km' => 2, 'fee' => 300],
            ['max_km' => 5, 'fee' => 600],
            ['max_km' => 10, 'fee' => 1000],
            ['max_km' => 20, 'fee' => 1800],
        ],
        // Zones géographiques (ex. Yaoundé) — surcharge si le point de livraison tombe dedans
        'zones' => [
            'centre_ville' => [
                'lat' => 3.8667,
                'lng' => 11.5167,
                'radius_km' => 2.5,
                'surcharge' => 0,
            ],
            'bastos' => [
                'lat' => 3.8920,
                'lng' => 11.5140,
                'radius_km' => 1.8,
                'surcharge' => 200,
            ],
            'douala_akwa' => [
                'lat' => 4.0500,
                'lng' => 9.7000,
                'radius_km' => 3.0,
                'surcharge' => 150,
            ],
        ],
    ],

];
