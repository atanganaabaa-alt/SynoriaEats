<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['restaurant', 'customer'])
            ->where('payment_status', PaymentStatus::Paid)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totals = Order::query()
            ->where('payment_status', PaymentStatus::Paid)
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->selectRaw('COALESCE(SUM(commission),0) as commissions, COALESCE(SUM(total),0) as revenue, COUNT(*) as count')
            ->first();

        return view('admin.commissions.index', [
            'orders' => $orders,
            'commissions' => (int) ($totals->commissions ?? 0),
            'revenue' => (int) ($totals->revenue ?? 0),
            'count' => (int) ($totals->count ?? 0),
            'rate' => (float) config('synoria.commission_rate', 0.10),
        ]);
    }
}
