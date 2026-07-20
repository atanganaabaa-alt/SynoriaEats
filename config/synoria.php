<?php

return [

    'commission_rate' => (float) env('SYNORIA_COMMISSION_RATE', 0.10),

    'payments' => [
        'sandbox' => (bool) env('SYNORIA_PAYMENTS_SANDBOX', true),
    ],

    'notifications' => [
        'driver' => env('SYNORIA_NOTIFIER', 'log'),
    ],

];
