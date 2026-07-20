<?php

namespace App\Services\Payments;

use App\Models\Order;

interface PaymentGateway
{
    public function charge(Order $order, string $phone): PaymentResult;
}
