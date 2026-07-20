<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\Order;

class PaymentManager
{
    public function __construct(
        private OrangeMoneyGateway $orangeMoney,
        private MtnMomoGateway $mtnMomo,
        private SandboxPaymentGateway $sandbox,
    ) {}

    public function charge(Order $order, PaymentMethod $method, string $phone): PaymentResult
    {
        $gateway = match ($method) {
            PaymentMethod::OrangeMoney => $this->orangeMoney,
            PaymentMethod::MtnMomo => $this->mtnMomo,
            default => $this->sandbox,
        };

        return $gateway->charge($order, $phone);
    }
}
