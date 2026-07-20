<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Services\Notifications\Notifier;

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

        if ($ownerPhone = $order->restaurant->owner?->phone) {
            $ownerMessage = sprintf(
                'SynoriaEats : nouvelle commande %s — %s FCFA. Consulte ton espace restaurateur.',
                $order->number,
                number_format($order->total, 0, ',', ' ')
            );
            $this->notifier->send($ownerPhone, $ownerMessage);
        }
    }

    public function handleOrderStatusChanged(OrderStatusChanged $event): void
    {
        $order = $event->order->loadMissing('customer');

        $message = sprintf(
            'SynoriaEats : commande %s — statut : %s.',
            $order->number,
            $order->status->label()
        );

        $this->notifier->send($order->delivery_phone, $message);
    }
}
