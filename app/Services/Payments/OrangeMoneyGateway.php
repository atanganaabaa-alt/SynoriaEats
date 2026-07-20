<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class OrangeMoneyGateway implements PaymentGateway
{
    public function __construct(
        private SandboxPaymentGateway $sandbox,
    ) {}

    public function charge(Order $order, string $phone): PaymentResult
    {
        if (config('synoria.payments.sandbox') || blank(config('services.orange_money.merchant_key'))) {
            return $this->sandbox->charge($order, $phone);
        }

        // Production : brancher l'API Orange Money ici.
        $reference = 'OM-'.Str::upper(Str::random(10));

        return PaymentResult::paid($reference);
    }
}
