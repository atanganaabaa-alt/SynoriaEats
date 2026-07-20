<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class LogNotifier implements Notifier
{
    public function __construct(
        private string $channel = 'log',
    ) {}

    public function send(string $to, string $message): void
    {
        Log::info("SynoriaEats [{$this->channel}]", [
            'to' => PhoneNumber::normalize($to),
            'message' => $message,
        ]);
    }
}
