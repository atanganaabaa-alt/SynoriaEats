<?php

namespace App\Services\Notifications;

class NotificationDispatcher implements Notifier
{
    /** @param  array<int, Notifier>  $channels */
    public function __construct(private array $channels) {}

    public function send(string $to, string $message): void
    {
        $errors = [];

        foreach ($this->channels as $channel) {
            try {
                $channel->send($to, $message);
            } catch (\Throwable $e) {
                $errors[] = $e;
                report($e);
            }
        }

        if ($errors !== [] && count($errors) === count($this->channels)) {
            throw $errors[0];
        }
    }
}
