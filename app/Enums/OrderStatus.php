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
            self::Pending => __('En attente'),
            self::Accepted => __('Acceptée'),
            self::Preparing => __('En préparation'),
            self::Ready => __('Prête'),
            self::OutForDelivery => __('En livraison'),
            self::Delivered => __('Livrée'),
            self::Cancelled => __('Annulée'),
        };
    }
}
