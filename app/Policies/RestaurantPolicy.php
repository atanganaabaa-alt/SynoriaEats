<?php

namespace App\Policies;

use App\Models\Restaurant;
use App\Models\User;

class RestaurantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRestaurantOwner() || $user->isAdmin();
    }

    public function view(User $user, Restaurant $restaurant): bool
    {
        return $user->isAdmin() || $restaurant->owner_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isRestaurantOwner();
    }

    public function update(User $user, Restaurant $restaurant): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Menu + cadre (logo/cover) uniquement après validation admin du restaurant.
        return $user->isRestaurantOwner()
            && $user->isApproved()
            && $restaurant->owner_id === $user->id
            && $restaurant->isApproved();
    }

    public function publish(User $user, Restaurant $restaurant): bool
    {
        return $this->update($user, $restaurant);
    }

    public function delete(User $user, Restaurant $restaurant): bool
    {
        return $this->update($user, $restaurant);
    }
}
