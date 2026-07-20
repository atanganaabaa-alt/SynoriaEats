<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (blank($sid) || blank($token) || blank($from)) {
            Log::warning('Twilio non configuré, notification ignorée.', compact('to', 'message'));

            return;
        }

        Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ])
            ->throw();
    }
}
