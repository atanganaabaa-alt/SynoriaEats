<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwilioWhatsAppNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.whatsapp_from');

        if (blank($sid) || blank($token) || blank($from)) {
            Log::warning('Twilio WhatsApp non configuré.', compact('to', 'message'));

            return;
        }

        $fromAddress = Str::startsWith($from, 'whatsapp:') ? $from : 'whatsapp:'.PhoneNumber::normalize($from);
        $toAddress = 'whatsapp:'.PhoneNumber::normalize($to);

        Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => $fromAddress,
                'To' => $toAddress,
                'Body' => $message,
            ])
            ->throw();
    }
}
