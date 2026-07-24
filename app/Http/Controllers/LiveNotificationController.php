<?php

namespace App\Http\Controllers;

use App\Notifications\OrderLiveUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LiveNotificationController extends Controller
{
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = $user->unreadNotifications()
            ->where('type', OrderLiveUpdate::class)
            ->latest()
            ->limit(10)
            ->get();

        $notifications = $items->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? 'SynoriaEats',
            'body' => $n->data['body'] ?? '',
            'url' => $n->data['url'] ?? route('orders.index'),
            'created_at' => $n->created_at?->toIso8601String(),
        ]);

        $items->markAsRead();

        return response()->json([
            'notifications' => $notifications->values(),
        ]);
    }
}
