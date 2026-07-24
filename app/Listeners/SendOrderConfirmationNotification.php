<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Notifications\OrderLiveUpdate;
use App\Services\Notifications\Notifier;
use Illuminate\Support\Facades\Notification;

class SendOrderConfirmationNotification
{
    public function __construct(private Notifier $notifier) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['customer', 'restaurant.owner']);

        $clientMessage = sprintf(
            'SynoriaEats : commande %s confirmée (%s FCFA). Merci !',
            $order->number,
            number_format($order->total, 0, ',', ' ')
        );

        $this->notifier->send($order->delivery_phone, $clientMessage);

        if ($order->customer) {
            $order->customer->notify(new OrderLiveUpdate(
                $order,
                'Commande confirmée',
                "{$order->number} · {$order->status->label()}"
            ));
        }

        if ($owner = $order->restaurant->owner) {
            if ($owner->phone) {
                $this->notifier->send(
                    $owner->phone,
                    sprintf(
                        'SynoriaEats : nouvelle commande %s — %s FCFA. Consulte ton espace restaurateur.',
                        $order->number,
                        number_format($order->total, 0, ',', ' ')
                    )
                );
            }

            $owner->notify(new OrderLiveUpdate(
                $order,
                'Nouvelle commande',
                "{$order->number} · {$order->restaurant->name}"
            ));
        }
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing(['customer', 'courier', 'restaurant.owner']);

        $message = sprintf(
            'SynoriaEats : commande %s — statut : %s.',
            $order->number,
            $order->status->label()
        );

        $this->notifier->send($order->delivery_phone, $message);

        $recipients = collect([$order->customer, $order->courier, $order->restaurant->owner])
            ->filter()
            ->unique('id');

        Notification::send($recipients, new OrderLiveUpdate(
            $order,
            'Mise à jour commande',
            "{$order->number} · {$order->status->label()}"
        ));

        if ($order->courier?->phone && in_array($order->status, [
            OrderStatus::Ready,
            OrderStatus::OutForDelivery,
            OrderStatus::Delivered,
        ], true)) {
            $this->notifier->send($order->courier->phone, $message);
        }

        if ($order->status === OrderStatus::Ready && $order->restaurant->owner?->phone) {
            $this->notifier->send(
                $order->restaurant->owner->phone,
                sprintf('SynoriaEats : commande %s prête — en attente d’un livreur.', $order->number)
            );
        }
    }
}
