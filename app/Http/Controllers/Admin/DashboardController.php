<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\AdminStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminStatsService $stats): View
    {
        $from = $request->filled('from')
            ? now()->parse($request->string('from'))->startOfDay()
            : now()->subDays(29)->startOfDay();
        $to = $request->filled('to')
            ? now()->parse($request->string('to'))->endOfDay()
            : now()->endOfDay();

        $overview = $stats->overview($from, $to);

        $recentOrders = Order::query()
            ->with(['restaurant', 'customer'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact('overview', 'recentOrders'));
    }
}
