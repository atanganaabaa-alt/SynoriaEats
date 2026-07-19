<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
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
            'address' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', 'string', 'max:60'],
            'opening_hours' => ['nullable', 'string', 'max:255'],
            'prep_time_min' => ['nullable', 'integer', 'min:5', 'max:180'],
            'prep_time_max' => ['nullable', 'integer', 'min:5', 'max:240'],
            'delivery_fee' => ['nullable', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_open' => ['sometimes', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ];
    }
}
