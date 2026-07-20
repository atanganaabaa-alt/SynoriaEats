<?php

namespace App\Services\Notifications;

class NotificationDispatcher implements Notifier
{
    /** @param  array<int, Notifier>  $channels */
    public function __construct(private array $channels) {}

    public function send(string $to, string $message): void
    {
        foreach ($this->channels as $channel) {
            $channel->send($to, $message);
        }
    }
}
