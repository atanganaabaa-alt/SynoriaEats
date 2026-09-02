<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case RestaurantOwner = 'restaurant_owner';
    case Courier = 'courier';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => __('Client'),
            self::RestaurantOwner => __('Restaurateur'),
            self::Courier => __('Livreur'),
            self::Admin => __('Admin'),
        };
    }
}
