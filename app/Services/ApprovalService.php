<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Models\Restaurant;
use App\Models\User;

class ApprovalService
{
    public function approveRestaurant(Restaurant $restaurant, ?string $notes = null): Restaurant
    {
        $restaurant->update([
            'status' => ApprovalStatus::Approved,
            'is_validated' => true,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            // Le resto reste fermé tant que le propriétaire n’a pas rempli menu / cadre.
            'is_open' => false,
        ]);

        $restaurant->owner?->update([
            'approval_status' => ApprovalStatus::Approved,
            'approval_notes' => $notes,
            'approved_at' => now(),
            'is_active' => true,
        ]);

        return $restaurant->fresh(['owner', 'documents']);
    }

    public function rejectRestaurant(Restaurant $restaurant, string $reason): Restaurant
    {
        $restaurant->update([
            'status' => ApprovalStatus::Rejected,
            'is_validated' => false,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);

        $restaurant->owner?->update([
            'approval_status' => ApprovalStatus::Rejected,
            'approval_notes' => $reason,
        ]);

        return $restaurant->fresh(['owner', 'documents']);
    }

    public function approveCourier(User $courier, ?string $notes = null): User
    {
        $courier->update([
            'approval_status' => ApprovalStatus::Approved,
            'approval_notes' => $notes,
            'approved_at' => now(),
            'is_active' => true,
        ]);

        return $courier->fresh();
    }

    public function rejectCourier(User $courier, string $reason): User
    {
        $courier->update([
            'approval_status' => ApprovalStatus::Rejected,
            'approval_notes' => $reason,
            'is_active' => false,
        ]);

        return $courier->fresh();
    }
}
