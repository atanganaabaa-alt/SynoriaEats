<?php

namespace App\Enums;

enum DocumentType: string
{
    case CommerceRegister = 'commerce_register';
    case Identity = 'identity';
    case ProofOfAddress = 'proof_of_address';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CommerceRegister => 'Registre de commerce / RCCM',
            self::Identity => 'Pièce d’identité',
            self::ProofOfAddress => 'Justificatif d’adresse du local',
            self::Other => 'Autre justificatif',
        };
    }
}
