<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'restaurant_id',
    'name',
    'description',
    'price',
    'photo_url',
    'category',
    'is_available',
])]
class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Linked accompaniment options for a dish.
     *
     * Pivot: `extra_price` (0 = inclus dans le prix du plat)
     */
    public function accompanimentOptions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'menu_item_accompaniment_options',
            'dish_id',
            'accompaniment_id'
        )->withPivot(['extra_price', 'is_available'])
            ->wherePivot('is_available', true)
            ->where('menu_items.is_available', true);
    }

    public function photoPublicUrl(): ?string
    {
        return \App\Support\MediaUrl::resolve($this->photo_url);
    }

    public function photoThumbnailUrl(): ?string
    {
        return \App\Support\MediaUrl::thumbnail($this->photo_url, 320);
    }
}
