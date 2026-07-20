<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class MtnMomoGateway implements PaymentGateway
{
    public function __construct(
        private SandboxPaymentGateway $sandbox,
    ) {}

    public function charge(Order $order, string $phone): PaymentResult
    {
        if (config('synoria.payments.sandbox') || blank(config('services.mtn_momo.subscription_key'))) {
            return $this->sandbox->charge($order, $phone);
        }

        // Production : brancher l'API MTN MoMo ici.
        $reference = 'MOMO-'.Str::upper(Str::random(10));

        return PaymentResult::paid($reference);
    }
}
