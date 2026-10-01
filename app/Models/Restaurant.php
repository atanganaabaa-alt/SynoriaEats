<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\SubscriptionPlan;
use Database\Factories\RestaurantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
    'subscription_plan',
    'trial_ends_at',
    'subscription_ends_at',
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
            'status' => ApprovalStatus::class,
            'reviewed_at' => 'datetime',
            'subscription_plan' => SubscriptionPlan::class,
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
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
        return $this->status === ApprovalStatus::Approved && $this->is_validated;
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function hasPaidSubscription(): bool
    {
        return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
    }

    /** Accès catalogue : essai actif OU abonnement payé actif. */
    public function hasCatalogAccess(): bool
    {
        return $this->onTrial() || $this->hasPaidSubscription();
    }

    public function isFeatured(): bool
    {
        return $this->hasPaidSubscription() && $this->subscription_plan === SubscriptionPlan::Pro;
    }

    public function subscriptionLabel(): string
    {
        if ($this->hasPaidSubscription() && $this->subscription_plan) {
            return $this->subscription_plan->label();
        }

        if ($this->onTrial()) {
            return __('Essai gratuit');
        }

        return __('Expiré');
    }

    public function accessEndsAt(): ?\Illuminate\Support\Carbon
    {
        $ends = collect([$this->trial_ends_at, $this->subscription_ends_at])
            ->filter(fn ($d) => $d !== null && $d->isFuture())
            ->sort()
            ->last();

        return $ends;
    }

    /**
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopeWithCatalogAccess(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->where('trial_ends_at', '>', now())
                ->orWhere('subscription_ends_at', '>', now());
        });
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
