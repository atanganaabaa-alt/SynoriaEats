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

    public function monthlyPrice(): int
    {
        return (int) config('synoria.subscriptions.plans.'.$this->value.'.price', match ($this) {
            self::Essentiel => 15000,
            self::Pro => 25000,
        });
    }

    public function commissionRate(): float
    {
        return (float) config('synoria.subscriptions.plans.'.$this->value.'.commission', match ($this) {
            self::Essentiel => 0.10,
            self::Pro => 0.08,
        });
    }

    public function description(): string
    {
        return match ($this) {
            self::Essentiel => __('Être listé, recevoir des commandes, commission 10 % sur les plats.'),
            self::Pro => __('Tout Essentiel, badge et mise en avant au catalogue, commission 8 %.'),
        };
    }
}
