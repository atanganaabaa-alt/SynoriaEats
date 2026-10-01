<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\NotchPayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotchPayWebhookController extends Controller
{
    public function webhook(Request $request, NotchPayClient $notchpay, OrderService $orders): JsonResponse
    {
        $payload = $request->getContent();
        if (! $notchpay->verifyWebhook($payload, $request->header('x-notch-signature'))) {
            return response()->json(['message' => 'Signature invalide.'], 403);
        }

        $data = json_decode($payload, true);
        if (! is_array($data)) {
            return response()->json(['message' => 'Corps invalide.'], 422);
        }

        $order = $this->findOrder($data);
        if (! $order) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        if ($this->isComplete($data)) {
            $orders->markPaid($order, (string) ($order->payment_reference ?: $order->number));
        } elseif ($this->isFailed($data) && $order->payment_status !== PaymentStatus::Paid) {
            $order->update(['payment_status' => PaymentStatus::Failed]);
        }

        return response()->json(['ok' => true]);
    }

    public function returned(Request $request, NotchPayClient $notchpay, OrderService $orders): RedirectResponse
    {
        $reference = (string) ($request->query('reference') ?: $request->query('trxref') ?: '');
        $order = Order::query()
            ->where('payment_reference', $reference)
            ->orWhere('number', $reference)
            ->first();

        if (! $order) {
            return redirect()->route('orders.index')->withErrors([
                'checkout' => 'Paiement introuvable.',
            ]);
        }

        if ($order->payment_reference && $notchpay->isConfigured()) {
            try {
                $remote = $notchpay->retrieve($order->payment_reference);
                if ($this->isComplete($remote)) {
                    $orders->markPaid($order, $order->payment_reference);
                }
            } catch (\Throwable) {
                // Le webhook peut encore confirmer le paiement.
            }
        }

        return redirect()->route('orders.show', $order);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function findOrder(array $data): ?Order
    {
        $candidates = array_filter([
            data_get($data, 'data.reference'),
            data_get($data, 'data.trxref'),
            data_get($data, 'data.merchant_reference'),
            data_get($data, 'reference'),
            data_get($data, 'transaction'),
            data_get($data, 'transaction.reference'),
            data_get($data, 'transaction.id'),
        ], fn ($value) => is_string($value) && $value !== '');

        foreach ($candidates as $reference) {
            $order = Order::query()
                ->where('payment_reference', $reference)
                ->orWhere('number', $reference)
                ->first();
            if ($order) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isComplete(array $data): bool
    {
        $event = strtolower((string) ($data['event'] ?? ''));
        $status = strtolower((string) (data_get($data, 'data.status') ?? data_get($data, 'transaction.status') ?? $data['status'] ?? ''));

        return str_contains($event, 'complete')
            || in_array($status, ['complete', 'completed', 'successful', 'success', 'paid'], true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isFailed(array $data): bool
    {
        $event = strtolower((string) ($data['event'] ?? ''));
        $status = strtolower((string) (data_get($data, 'data.status') ?? $data['status'] ?? ''));

        return str_contains($event, 'fail')
            || str_contains($event, 'cancel')
            || in_array($status, ['failed', 'canceled', 'cancelled', 'expired'], true);
    }
}
