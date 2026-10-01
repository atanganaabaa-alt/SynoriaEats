<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;

class NotchPayGateway
{
    public function __construct(
        private NotchPayClient $client,
    ) {}

    public function handles(PaymentMethod $method): bool
    {
        if (config('synoria.payments.sandbox') || ! $this->client->isConfigured()) {
            return false;
        }

        return in_array($method, [PaymentMethod::OrangeMoney, PaymentMethod::MtnMomo], true);
    }

    public function charge(Order $order, string $phone, PaymentMethod $method): PaymentResult
    {
        $order->loadMissing('customer');
        $channel = $method === PaymentMethod::OrangeMoney ? 'cm.orange' : 'cm.mtn';

        try {
            $created = $this->client->initialize([
                'amount' => (int) $order->total,
                'currency' => 'XAF',
                'description' => 'Commande '.$order->number,
                'reference' => $order->number,
                'callback' => route('payments.notchpay.return'),
                'email' => $order->customer?->email,
                'phone' => NotchPayClient::normalizePhone($phone),
                'locked_currency' => 'XAF',
                'locked_country' => 'CM',
                'locked_channel' => $channel,
                'customer' => [
                    'name' => $order->customer?->name ?: 'Client SynoriaEats',
                    'email' => $order->customer?->email,
                    'phone' => NotchPayClient::normalizePhone($phone),
                ],
            ]);
        } catch (\Throwable $e) {
            return PaymentResult::failed($e->getMessage());
        }

        $transaction = $created['transaction'] ?? null;
        $reference = is_array($transaction)
            ? (string) ($transaction['reference'] ?? '')
            : (string) $transaction;
        $redirect = (string) ($created['authorization_url'] ?? '');

        if ($reference === '' || $redirect === '') {
            return PaymentResult::failed('NotchPay n’a pas renvoyé de page de paiement.');
        }

        return PaymentResult::pending($reference, $redirect);
    }
}
