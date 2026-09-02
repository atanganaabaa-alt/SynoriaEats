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
        return __($this->value);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Catégories visibles sur le menu client (hors accompagnements liés aux plats). */
    public function visibleInClientCatalog(): bool
    {
        return $this !== self::Accompagnements;
    }

    /**
     * @return list<string>
     */
    public static function clientCatalogOrder(): array
    {
        return [
            self::Plats->value,
            self::Boissons->value,
            self::Desserts->value,
        ];
    }
}
