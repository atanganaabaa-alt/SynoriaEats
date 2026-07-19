<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Accepted => 'Acceptée',
            self::Preparing => 'En préparation',
            self::Ready => 'Prête',
            self::OutForDelivery => 'En livraison',
            self::Delivered => 'Livrée',
            self::Cancelled => 'Annulée',
        };
    }
}
