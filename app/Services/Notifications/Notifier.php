<?php

namespace App\Services\Notifications;

interface Notifier
{
    public function send(string $to, string $message): void;
}
