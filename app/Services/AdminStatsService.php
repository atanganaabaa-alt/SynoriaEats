<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;

class AdminStatsService
{
    /**
     * @return array{
     *     revenue: int,
     *     commissions: int,
     *     orders_paid: int,
     *     deliveries: int,
     *     avg_restaurant_rating: float,
     *     avg_courier_rating: float,
     *     users_total: int,
     *     restaurants_pending: int,
     *     restaurants_active: int,
     *     revenue_by_day: array<int, array{date: string, total: int}>
     * }
     */
    public function overview(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(29)->startOfDay();
        $to ??= now()->endOfDay();

        $paidOrders = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$from, $to]);

        $revenue = (int) (clone $paidOrders)->sum('total');
        $commissions = (int) (clone $paidOrders)->sum('commission');
        $ordersPaid = (clone $paidOrders)->count();

        $deliveries = Order::query()
            ->where('status', OrderStatus::Delivered)
            ->whereBetween('delivered_at', [$from, $to])
            ->count();

        $avgRestaurant = (float) Review::query()
            ->whereBetween('created_at', [$from, $to])
            ->avg('restaurant_rating');

        $avgCourier = (float) Review::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('courier_rating')
            ->avg('courier_rating');

        $revenueByDay = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'date' => (string) $row->day,
                'total' => (int) $row->total,
            ])
            ->all();

        return [
            'revenue' => $revenue,
            'commissions' => $commissions,
            'orders_paid' => $ordersPaid,
            'deliveries' => $deliveries,
            'avg_restaurant_rating' => round($avgRestaurant, 1),
            'avg_courier_rating' => round($avgCourier, 1),
            'users_total' => User::query()->count(),
            'restaurants_pending' => Restaurant::query()->where('is_validated', false)->count(),
            'restaurants_active' => Restaurant::query()->where('is_validated', true)->where('is_open', true)->count(),
            'revenue_by_day' => $revenueByDay,
            'from' => $from,
            'to' => $to,
            'commission_rate' => (float) config('synoria.commission_rate', 0.10),
        ];
    }
}
