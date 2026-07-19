<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'phone',
    'role',
    'password',
    'google_id',
    'avatar_url',
    'rating',
    'delivery_count',
    'is_active',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'rating' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function ownedRestaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'owner_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'courier_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'customer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isRestaurantOwner(): bool
    {
        return $this->role === UserRole::RestaurantOwner;
    }

    public function isCourier(): bool
    {
        return $this->role === UserRole::Courier;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }
}
