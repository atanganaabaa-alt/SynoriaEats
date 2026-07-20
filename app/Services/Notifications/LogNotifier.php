<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Log;

class LogNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        Log::info('SynoriaEats notification', [
            'to' => $to,
            'message' => $message,
        ]);
    }
}
