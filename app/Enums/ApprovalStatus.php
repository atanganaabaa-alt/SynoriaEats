<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En attente'),
            self::Approved => __('Approuvé'),
            self::Rejected => __('Rejeté'),
        };
    }
}
