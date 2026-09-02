<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En attente'),
            self::Paid => __('Payé'),
            self::Failed => __('Échoué'),
            self::Refunded => __('Remboursé'),
        };
    }
}
