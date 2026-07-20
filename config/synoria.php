<?php

return [

    'commission_rate' => (float) env('SYNORIA_COMMISSION_RATE', 0.10),

    'payments' => [
        'sandbox' => (bool) env('SYNORIA_PAYMENTS_SANDBOX', true),
    ],

    'notifications' => [
        // Channels: log (dev), sms, whatsapp (Twilio), orange_sms — e.g. orange_sms,whatsapp
        'channels' => env('SYNORIA_NOTIFICATION_CHANNELS', 'log'),
    ],

];
