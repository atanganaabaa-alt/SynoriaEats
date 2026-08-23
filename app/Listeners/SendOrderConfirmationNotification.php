<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Services\Notifications\NotificationAudit;

class SendOrderConfirmationNotification
{
    public function __construct(private NotificationAudit $audit) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['customer', 'restaurant.owner']);

        $clientMessage = sprintf(
            'SynoriaEats : commande %s confirmée (%s FCFA). Merci !',
            $order->number,
            number_format($order->total, 0, ',', ' ')
        );

        $this->audit->sms($order, $order->delivery_phone, $clientMessage);
        $this->audit->inApp($order, $order->customer, 'Commande confirmée', "{$order->number} · {$order->status->label()}");

        if ($owner = $order->restaurant->owner) {
            $this->audit->sms(
                $order,
                $owner->phone,
                sprintf(
                    'SynoriaEats : nouvelle commande %s — %s FCFA. Consulte ton espace restaurateur.',
                    $order->number,
                    number_format($order->total, 0, ',', ' ')
                )
            );
            $this->audit->inApp($order, $owner, 'Nouvelle commande', "{$order->number} · {$order->restaurant->name}");
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

        $this->audit->sms($order, $order->delivery_phone, $message);
        $this->audit->inApp($order, $order->customer, 'Mise à jour commande', "{$order->number} · {$order->status->label()}");
        $this->audit->inApp($order, $order->restaurant->owner, 'Mise à jour commande', "{$order->number} · {$order->status->label()}");
        $this->audit->inApp($order, $order->courier, 'Mise à jour commande', "{$order->number} · {$order->status->label()}");

        if ($order->courier?->phone && in_array($order->status, [
            OrderStatus::Ready,
            OrderStatus::OutForDelivery,
            OrderStatus::Delivered,
        ], true)) {
            $this->audit->sms($order, $order->courier->phone, $message);
        }

        if ($order->status === OrderStatus::Ready && $order->restaurant->owner?->phone) {
            $this->audit->sms(
                $order,
                $order->restaurant->owner->phone,
                sprintf('SynoriaEats : commande %s prête — en attente d’un livreur.', $order->number)
            );
        }
    }
}
