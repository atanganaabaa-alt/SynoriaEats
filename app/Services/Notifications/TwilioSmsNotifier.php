<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TwilioSmsNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.sms_from');

        if (blank($sid) || blank($token) || blank($from)) {
            Log::warning('Twilio SMS non configuré.', compact('to', 'message'));

            return;
        }

        Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $from,
                'To' => PhoneNumber::normalize($to),
                'Body' => $message,
            ])
            ->throw();
    }
}
