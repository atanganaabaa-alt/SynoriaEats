<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Essentiel = 'essentiel';
    case Pro = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::Essentiel => __('Essentiel'),
            self::Pro => __('Pro'),
        };
    }

    public function yearlyPrice(): int
    {
        return (int) config('synoria.subscriptions.plans.'.$this->value.'.price', match ($this) {
            self::Essentiel => 15000,
            self::Pro => 25000,
        });
    }

    public function description(): string
    {
        return match ($this) {
            self::Essentiel => __('Être listé sur SynoriaEats + commandes + commission 10 %.'),
            self::Pro => __('Tout Essentiel + mise en avant catalogue + badge Pro.'),
        };
    }
}
