<?php

namespace App\Enums;

enum MenuCategory: string
{
    case Plats = 'Plats';
    case Boissons = 'Boissons';
    case Accompagnements = 'Accompagnements';
    case Desserts = 'Desserts';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
