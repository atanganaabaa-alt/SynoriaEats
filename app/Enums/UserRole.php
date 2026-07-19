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
            self::Customer => 'Client',
            self::RestaurantOwner => 'Restaurateur',
            self::Courier => 'Livreur',
            self::Admin => 'Admin',
        };
    }
}
