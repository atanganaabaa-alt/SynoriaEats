<?php

namespace App\Services\Payments;

use App\Models\Order;
use Illuminate\Support\Str;

class SandboxPaymentGateway implements PaymentGateway
{
    public function charge(Order $order, string $phone): PaymentResult
    {
        return PaymentResult::paid('SANDBOX-'.Str::upper(Str::random(12)));
    }
}
