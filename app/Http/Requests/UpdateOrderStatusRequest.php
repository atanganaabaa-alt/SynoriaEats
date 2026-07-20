<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Order $order */
        $order = $this->route('order');

        return $this->user()?->isAdmin()
            || ($this->user()?->role === UserRole::RestaurantOwner
                && $order->restaurant->owner_id === $this->user()->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([
                OrderStatus::Accepted->value,
                OrderStatus::Preparing->value,
                OrderStatus::Ready->value,
                OrderStatus::Cancelled->value,
            ])],
        ];
    }
}
