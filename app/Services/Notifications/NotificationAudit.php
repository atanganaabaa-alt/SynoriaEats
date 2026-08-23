<?php

namespace App\Services\Notifications;

use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderLiveUpdate;
use Illuminate\Support\Str;
use Throwable;

class NotificationAudit
{
    public function __construct(private Notifier $notifier) {}

    public function sms(Order $order, ?string $to, string $message): void
    {
        if (blank($to)) {
            return;
        }

        $this->attempt($order, 'sms', $to, $message, function () use ($to, $message) {
            $this->notifier->send($to, $message);
        });
    }

    public function inApp(Order $order, ?User $user, string $title, string $body): void
    {
        if (! $user) {
            return;
        }

        $recipient = $user->email ?: (string) $user->id;

        $this->attempt($order, 'in_app', $recipient, $title.' — '.$body, function () use ($user, $order, $title, $body) {
            $user->notify(new OrderLiveUpdate($order, $title, $body));
        });
    }

    private function attempt(Order $order, string $channel, string $recipient, string $message, callable $send): void
    {
        try {
            $send();

            NotificationDelivery::query()->create([
                'order_id' => $order->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'message' => $message,
                'status' => 'sent',
            ]);
        } catch (Throwable $e) {
            NotificationDelivery::query()->create([
                'order_id' => $order->id,
                'channel' => $channel,
                'recipient' => $recipient,
                'message' => $message,
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 500),
            ]);

            report($e);
        }
    }
}
