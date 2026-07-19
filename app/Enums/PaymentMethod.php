<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Card = 'card';
    case Cash = 'cash';
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';

    public function label(): string
    {
        return match ($this) {
            self::Card => 'Carte',
            self::Cash => 'Espèces',
            self::OrangeMoney => 'Orange Money',
            self::MtnMomo => 'MTN MoMo',
        };
    }
}
