<?php

namespace App\Models;

use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'name',
    'slug',
    'address',
    'description',
    'logo_url',
    'cover_url',
    'category',
    'opening_hours',
    'rating',
    'review_count',
    'prep_time_min',
    'prep_time_max',
    'delivery_fee',
    'latitude',
    'longitude',
    'is_open',
    'is_validated',
    'status',
    'rejection_reason',
    'reviewed_at',
])]
class Restaurant extends Model
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'decimal:1',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_open' => 'boolean',
            'is_validated' => 'boolean',
            'status' => \App\Enums\ApprovalStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RestaurantDocument::class);
    }

    public function isApproved(): bool
    {
        return $this->status === \App\Enums\ApprovalStatus::Approved && $this->is_validated;
    }

    public function logoPublicUrl(): ?string
    {
        return \App\Support\MediaUrl::resolve($this->logo_url);
    }

    public function coverPublicUrl(): ?string
    {
        return \App\Support\MediaUrl::resolve($this->cover_url);
    }

    public function coverThumbnailUrl(): ?string
    {
        return \App\Support\MediaUrl::thumbnail($this->cover_url, 640);
    }

    public function logoThumbnailUrl(): ?string
    {
        return \App\Support\MediaUrl::thumbnail($this->logo_url, 128);
    }
}
