<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Restaurant $restaurant */
        $restaurant = $this->route('restaurant');

        return $this->user()?->isAdmin()
            || ($this->user()?->role === UserRole::RestaurantOwner
                && $restaurant->owner_id === $this->user()->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:60'],
            'is_available' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
