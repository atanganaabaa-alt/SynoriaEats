<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Models\Order;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Order $order, ReviewService $reviews): RedirectResponse
    {
        try {
            $reviews->leave($order, $request->user(), $request->validated());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('status', 'Merci pour ton avis !');
    }
}
