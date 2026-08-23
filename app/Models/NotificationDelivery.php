<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'channel',
    'recipient',
    'message',
    'status',
    'error',
])]
class NotificationDelivery extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function wasSent(): bool
    {
        return $this->status === 'sent';
    }
}
