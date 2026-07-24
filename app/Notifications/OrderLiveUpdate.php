<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderLiveUpdate extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $title,
        public string $body,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'number' => $this->order->number,
            'status' => $this->order->status->value,
            'status_label' => $this->order->status->label(),
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->urlFor($notifiable),
        ];
    }

    private function urlFor(object $notifiable): string
    {
        if (method_exists($notifiable, 'isRestaurantOwner') && $notifiable->isRestaurantOwner()) {
            return route('owner.orders.show', $this->order);
        }

        if (method_exists($notifiable, 'isCourier') && $notifiable->isCourier()) {
            return route('courier.missions.show', $this->order);
        }

        return route('orders.show', $this->order);
    }
}
