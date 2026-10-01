<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NotchPayClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.notchpay.public_key'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function initialize(array $payload): array
    {
        return $this->decode($this->request()->post($this->url('/payments'), $payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieve(string $reference): array
    {
        return $this->decode($this->request()->get($this->url('/payments/'.rawurlencode($reference))));
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $reference): array
    {
        return $this->decode($this->request()->delete($this->url('/payments/'.rawurlencode($reference))));
    }

    public function verifyWebhook(string $payload, ?string $signature): bool
    {
        $hash = (string) config('services.notchpay.hash_key');
        if ($hash === '' || $signature === null || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $hash);

        return hash_equals($expected, $signature);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (Str::startsWith($digits, '237')) {
            return $digits;
        }
        if (Str::startsWith($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return '237'.$digits;
    }

    private function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->withHeaders([
                'Authorization' => (string) config('services.notchpay.public_key'),
            ]);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.notchpay.base_url'), '/').$path;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $json = $response->json();
        if (! is_array($json)) {
            $json = [];
        }

        if ($response->failed()) {
            $message = (string) ($json['message'] ?? 'NotchPay a refusé la requête.');

            throw new \RuntimeException($message);
        }

        return $json;
    }
}
