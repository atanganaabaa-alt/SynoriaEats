<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReviewService
{
    /**
     * @param  array{restaurant_rating: int, courier_rating?: int|null, comment?: string|null}  $data
     */
    public function leave(Order $order, User $customer, array $data): Review
    {
        abort_unless($order->customer_id === $customer->id, 403);

        if ($order->status !== OrderStatus::Delivered) {
            throw new InvalidArgumentException('Tu ne peux noter qu’une commande livrée.');
        }

        if ($order->review()->exists()) {
            throw new InvalidArgumentException('Cette commande a déjà été notée.');
        }

        return DB::transaction(function () use ($order, $customer, $data) {
            $review = Review::query()->create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'restaurant_id' => $order->restaurant_id,
                'courier_id' => $order->courier_id,
                'restaurant_rating' => (int) $data['restaurant_rating'],
                'courier_rating' => isset($data['courier_rating']) ? (int) $data['courier_rating'] : null,
                'comment' => $data['comment'] ?? null,
            ]);

            $this->recomputeRestaurantRating($order->restaurant_id);
            if ($order->courier_id) {
                $this->recomputeCourierRating($order->courier_id);
            }

            return $review;
        });
    }

    private function recomputeRestaurantRating(int $restaurantId): void
    {
        $stats = Review::query()
            ->where('restaurant_id', $restaurantId)
            ->selectRaw('AVG(restaurant_rating) as avg_rating, COUNT(*) as total')
            ->first();

        Restaurant::query()->whereKey($restaurantId)->update([
            'rating' => round((float) ($stats->avg_rating ?? 0), 1),
            'review_count' => (int) ($stats->total ?? 0),
        ]);
    }

    private function recomputeCourierRating(int $courierId): void
    {
        $avg = Review::query()
            ->where('courier_id', $courierId)
            ->whereNotNull('courier_rating')
            ->avg('courier_rating');

        User::query()->whereKey($courierId)->update([
            'rating' => round((float) ($avg ?? 0), 1),
        ]);
    }
}
