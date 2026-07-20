<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrangeSmsNotifier implements Notifier
{
    public function send(string $to, string $message): void
    {
        $clientId = config('services.orange_sms.client_id');
        $clientSecret = config('services.orange_sms.client_secret');
        $sender = (string) config('services.orange_sms.sender', '2370000');

        if (blank($clientId) || blank($clientSecret)) {
            Log::warning('Orange SMS non configuré.', compact('to', 'message'));

            return;
        }

        $token = $this->accessToken($clientId, $clientSecret);
        $senderDigits = preg_replace('/\D+/', '', $sender) ?? $sender;
        $senderAddress = rawurlencode('tel:+'.$senderDigits);
        $recipient = 'tel:'.PhoneNumber::normalize($to);

        Http::withToken($token)
            ->acceptJson()
            ->post("https://api.orange.com/smsmessaging/v1/outbound/{$senderAddress}/requests", [
                'outboundSMSMessageRequest' => [
                    'address' => $recipient,
                    'senderAddress' => 'tel:+'.$senderDigits,
                    'outboundSMSTextMessage' => [
                        'message' => $message,
                    ],
                ],
            ])
            ->throw();
    }

    private function accessToken(string $clientId, string $clientSecret): string
    {
        return Cache::remember('synoria.orange_sms_token', 3300, function () use ($clientId, $clientSecret) {
            $response = Http::asForm()
                ->withBasicAuth($clientId, $clientSecret)
                ->post('https://api.orange.com/oauth/v3/token', [
                    'grant_type' => 'client_credentials',
                ])
                ->throw();

            return (string) $response->json('access_token');
        });
    }
}
