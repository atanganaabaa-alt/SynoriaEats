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
        private NotchPayGateway $notchPay,
    ) {}

    public function charge(Order $order, PaymentMethod $method, string $phone): PaymentResult
    {
        if ($this->notchPay->handles($method)) {
            return $this->notchPay->charge($order, $phone, $method);
        }

        $gateway = match ($method) {
            PaymentMethod::OrangeMoney => $this->orangeMoney,
            PaymentMethod::MtnMomo => $this->mtnMomo,
            default => $this->sandbox,
        };

        return $gateway->charge($order, $phone);
    }
}
